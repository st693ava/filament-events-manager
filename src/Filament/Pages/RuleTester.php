<?php

namespace St693ava\FilamentEventsManager\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Services\MockDataGenerator;
use St693ava\FilamentEventsManager\Services\RuleTestRunner;
use St693ava\FilamentEventsManager\Support\EventContext;

class RuleTester extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;
    protected static ?string $navigationLabel = 'Testador de Regras';
    protected static ?string $title = 'Testador de Regras';
    protected static ?int $navigationSort = 2;

    public function getView(): string
    {
        return 'filament-events-manager::pages.rule-tester';
    }

    public ?int $selectedRuleId = null;
    public bool $dryRun = true;
    public bool $verboseLogging = true;
    public string $mockDataType = 'auto';
    public array $customMockData = [];
    public ?string $lastTestResult = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('selectedRuleId')
                            ->label('Regra a Testar')
                            ->options(EventRule::where('is_active', true)->pluck('name', 'id'))
                            ->placeholder('Selecione uma regra para testar')
                            ->required()
                            ->live()
                            ->searchable(),

                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                Toggle::make('dryRun')
                                    ->label('Modo Dry-Run')
                                    ->helperText('Simula execução sem executar ações reais')
                                    ->default(true),

                                Toggle::make('verboseLogging')
                                    ->label('Logging Verboso')
                                    ->helperText('Ativa logs detalhados para debugging')
                                    ->default(true),
                            ]),
                    ]),

                Select::make('mockDataType')
                    ->label('Tipo de Dados de Teste')
                    ->options([
                        'auto' => 'Automático (baseado na regra)',
                        'custom' => 'Personalizado',
                        'realistic' => 'Dados Realísticos',
                        'edge_cases' => 'Casos Extremos',
                    ])
                    ->default('auto')
                    ->live()
                    ->columnSpanFull(),

                KeyValue::make('customMockData')
                    ->label('Dados Personalizados')
                    ->keyLabel('Campo')
                    ->valueLabel('Valor')
                    ->visible(fn ($get) => $get('mockDataType') === 'custom')
                    ->helperText('Dados JSON para simulação. Ex: {"name": "João", "email": "joao@exemplo.com"}')
                    ->columnSpanFull(),

                Placeholder::make('rule_info')
                    ->label('Informações da Regra')
                    ->content(function ($get) {
                        if (! $get('selectedRuleId')) {
                            return 'Selecione uma regra para ver as informações.';
                        }

                        $rule = EventRule::find($get('selectedRuleId'));
                        if (! $rule) {
                            return 'Regra não encontrada.';
                        }

                        return view('filament-events-manager::components.rule-info', [
                            'rule' => $rule,
                        ])->render();
                    })
                    ->columnSpanFull(),

                Textarea::make('lastTestResult')
                    ->label('Resultado do Último Teste')
                    ->rows(10)
                    ->disabled()
                    ->visible(fn ($get) => ! empty($this->lastTestResult))
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_rule')
                ->label('Executar Teste')
                ->icon(Heroicon::OutlinedPlay)
                ->color('success')
                ->action('testRule')
                ->disabled(fn () => ! $this->selectedRuleId),

            Action::make('clear_results')
                ->label('Limpar Resultados')
                ->icon(Heroicon::OutlinedTrash)
                ->color('gray')
                ->action('clearResults')
                ->visible(fn () => ! empty($this->lastTestResult)),

            Action::make('export_scenario')
                ->label('Exportar Cenário')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('info')
                ->action('exportScenario')
                ->disabled(fn () => ! $this->selectedRuleId),
        ];
    }

    public function testRule(): void
    {
        $rule = EventRule::find($this->selectedRuleId);
        if (! $rule) {
            Notification::make()
                ->title('Erro')
                ->body('Regra não encontrada.')
                ->danger()
                ->send();
            return;
        }

        try {
            // Generate mock data
            $mockGenerator = new MockDataGenerator();
            $mockData = $this->generateMockData($mockGenerator, $rule);

            // Run the test
            $testRunner = new RuleTestRunner();
            $testRunner->setDryRun($this->dryRun);
            $testRunner->setVerboseLogging($this->verboseLogging);

            $result = $testRunner->testRule($rule, $mockData);

            $this->lastTestResult = $this->formatTestResult($result);

            Notification::make()
                ->title('Teste Executado')
                ->body('Teste concluído. Verifique os resultados abaixo.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Rule test failed', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->lastTestResult = "❌ ERRO NO TESTE\n\n" .
                "Mensagem: {$e->getMessage()}\n" .
                "Linha: {$e->getLine()}\n" .
                "Arquivo: {$e->getFile()}\n\n" .
                "Stack Trace:\n{$e->getTraceAsString()}";

            Notification::make()
                ->title('Erro no Teste')
                ->body('Ocorreu um erro durante o teste da regra.')
                ->danger()
                ->send();
        }
    }

    public function clearResults(): void
    {
        $this->lastTestResult = null;

        Notification::make()
            ->title('Resultados Limpos')
            ->body('Os resultados do teste foram removidos.')
            ->success()
            ->send();
    }

    public function exportScenario(): void
    {
        $rule = EventRule::find($this->selectedRuleId);
        if (! $rule) {
            return;
        }

        $scenario = [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'test_settings' => [
                'dry_run' => $this->dryRun,
                'verbose_logging' => $this->verboseLogging,
                'mock_data_type' => $this->mockDataType,
                'custom_mock_data' => $this->customMockData,
            ],
            'exported_at' => now()->toISOString(),
        ];

        $this->dispatch('download-scenario', json_encode($scenario, JSON_PRETTY_PRINT));

        Notification::make()
            ->title('Cenário Exportado')
            ->body('O cenário de teste foi preparado para download.')
            ->success()
            ->send();
    }

    private function generateMockData(MockDataGenerator $generator, EventRule $rule): array
    {
        return match ($this->mockDataType) {
            'custom' => $this->customMockData,
            'realistic' => $generator->generateRealisticData($rule),
            'edge_cases' => $generator->generateEdgeCases($rule),
            default => $generator->generateAutoData($rule),
        };
    }

    private function formatTestResult(array $result): string
    {
        $output = "🧪 RESULTADO DO TESTE\n";
        $output .= str_repeat("=", 50) . "\n\n";

        // Test summary
        $output .= "📊 RESUMO:\n";
        $output .= "• Status: " . ($result['success'] ? "✅ Sucesso" : "❌ Falha") . "\n";
        $output .= "• Tempo de Execução: {$result['execution_time']}ms\n";
        $output .= "• Modo: " . ($this->dryRun ? "Dry-Run" : "Execução Real") . "\n";
        $output .= "• Condições Avaliadas: {$result['conditions_evaluated']}\n";
        $output .= "• Ações Executadas: {$result['actions_executed']}\n\n";

        // Conditions evaluation
        if (! empty($result['condition_results'])) {
            $output .= "🔍 AVALIAÇÃO DE CONDIÇÕES:\n";
            foreach ($result['condition_results'] as $i => $condition) {
                $status = $condition['result'] ? "✅" : "❌";
                $output .= "• Condição " . ($i + 1) . ": {$status} {$condition['expression']}\n";
                if (isset($condition['details'])) {
                    $output .= "  └─ {$condition['details']}\n";
                }
            }
            $output .= "\n";
        }

        // Actions execution
        if (! empty($result['action_results'])) {
            $output .= "⚡ EXECUÇÃO DE AÇÕES:\n";
            foreach ($result['action_results'] as $i => $action) {
                $status = $action['success'] ? "✅" : "❌";
                $output .= "• Ação " . ($i + 1) . " ({$action['type']}): {$status}\n";
                if (isset($action['details'])) {
                    $output .= "  └─ {$action['details']}\n";
                }
            }
            $output .= "\n";
        }

        // Debug information
        if ($this->verboseLogging && ! empty($result['debug_log'])) {
            $output .= "🐛 LOG DE DEBUG:\n";
            foreach ($result['debug_log'] as $logEntry) {
                $output .= "• [{$logEntry['level']}] {$logEntry['message']}\n";
            }
            $output .= "\n";
        }

        // Mock data used
        if (! empty($result['mock_data'])) {
            $output .= "📋 DADOS DE TESTE UTILIZADOS:\n";
            $output .= json_encode($result['mock_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $output .= "\n\n";
        }

        $output .= str_repeat("=", 50) . "\n";
        $output .= "Teste concluído em " . now()->format('d/m/Y H:i:s');

        return $output;
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-events-manager.filament.navigation_group', 'Gestão de Eventos');
    }

    public function loadScenario(string $scenario): void
    {
        $mockGenerator = new MockDataGenerator();
        $scenarioData = $mockGenerator->generateScenarioData($scenario);

        $this->mockDataType = 'custom';
        $this->customMockData = $scenarioData;

        $this->form->fill([
            'mockDataType' => 'custom',
            'customMockData' => $scenarioData,
        ]);

        Notification::make()
            ->title('Cenário Carregado')
            ->body("Cenário '{$scenario}' foi carregado com sucesso.")
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return true; // Adjust based on your authorization needs
    }
}