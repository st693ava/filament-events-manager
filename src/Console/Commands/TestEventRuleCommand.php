<?php

namespace St693ava\FilamentEventsManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Services\MockDataGenerator;
use St693ava\FilamentEventsManager\Services\RuleTestRunner;

class TestEventRuleCommand extends Command
{
    protected $signature = 'events:test-rule
                            {rule? : Rule ID or name to test}
                            {--all : Test all active rules}
                            {--dry-run : Simulate execution without running real actions}
                            {--detailed : Enable detailed logging}
                            {--data= : JSON string with custom mock data}
                            {--scenario= : Predefined scenario (user_registration, order_created, product_updated)}
                            {--format=table : Output format (table, json, detail)}';

    protected $description = 'Test event rules with mock data';

    public function handle(): int
    {
        $this->info('🧪 Filament Events Manager - Rule Tester');
        $this->newLine();

        try {
            if ($this->option('all')) {
                return $this->testAllRules();
            }

            $rule = $this->resolveRule();
            if (! $rule) {
                return self::FAILURE;
            }

            return $this->testSingleRule($rule);

        } catch (\Exception $e) {
            $this->error("Erro inesperado: {$e->getMessage()}");
            if ($this->option('detailed')) {
                $this->error("Stack trace: {$e->getTraceAsString()}");
            }
            return self::FAILURE;
        }
    }

    /**
     * Test all active rules
     */
    private function testAllRules(): int
    {
        $rules = EventRule::where('is_active', true)->get();

        if ($rules->isEmpty()) {
            $this->warn('Nenhuma regra ativa encontrada.');
            return self::SUCCESS;
        }

        $this->info("Testando {$rules->count()} regras ativas...");
        $this->newLine();

        $results = [];
        $passed = 0;
        $failed = 0;

        foreach ($rules as $rule) {
            $this->line("Testando: {$rule->name} (ID: {$rule->id})");

            $result = $this->runTest($rule);
            $results[] = [
                'rule' => $rule,
                'result' => $result,
            ];

            if ($result['success']) {
                $this->info("  ✅ Sucesso ({$result['execution_time']}ms)");
                $passed++;
            } else {
                $this->error("  ❌ Falha ({$result['execution_time']}ms)");
                if (isset($result['error'])) {
                    $this->line("     Erro: {$result['error']}");
                }
                $failed++;
            }
        }

        $this->newLine();
        $this->displaySummary($passed, $failed, $results);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Test a single rule
     */
    private function testSingleRule(EventRule $rule): int
    {
        $this->info("Testando regra: {$rule->name} (ID: {$rule->id})");
        $this->line("Tipo: {$rule->trigger_type}");
        $this->line("Condições: {$rule->conditions->count()}");
        $this->line("Ações: {$rule->actions->count()}");
        $this->newLine();

        $result = $this->runTest($rule);

        $this->displayDetailedResult($result);

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Resolve rule from argument
     */
    private function resolveRule(): ?EventRule
    {
        $ruleInput = $this->argument('rule');

        if (! $ruleInput) {
            $rules = EventRule::where('is_active', true)->get();

            if ($rules->isEmpty()) {
                $this->warn('Nenhuma regra ativa encontrada.');
                return null;
            }

            $choices = $rules->mapWithKeys(function ($rule) {
                return [$rule->id => "{$rule->name} (ID: {$rule->id})"];
            })->toArray();

            $selectedId = $this->choice('Selecione uma regra para testar:', $choices);

            return $rules->find($selectedId);
        }

        // Try to find by ID first
        if (is_numeric($ruleInput)) {
            $rule = EventRule::find($ruleInput);
            if ($rule) {
                return $rule;
            }
        }

        // Try to find by name
        $rule = EventRule::where('name', 'like', "%{$ruleInput}%")->first();
        if ($rule) {
            return $rule;
        }

        $this->error("Regra '{$ruleInput}' não encontrada.");
        return null;
    }

    /**
     * Run the actual test
     */
    private function runTest(EventRule $rule): array
    {
        $mockGenerator = new MockDataGenerator();
        $testRunner = new RuleTestRunner();

        // Configure test runner
        $testRunner->setDryRun($this->option('dry-run') !== false);
        $testRunner->setVerboseLogging($this->option('detailed'));

        // Generate mock data
        $mockData = $this->generateMockData($mockGenerator, $rule);

        // Run the test
        return $testRunner->testRule($rule, $mockData);
    }

    /**
     * Generate mock data based on options
     */
    private function generateMockData(MockDataGenerator $generator, EventRule $rule): array
    {
        // Custom data from option
        if ($customData = $this->option('data')) {
            $decoded = json_decode($customData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            $this->warn("Dados JSON inválidos fornecidos, usando dados automáticos.");
        }

        // Predefined scenario
        if ($scenario = $this->option('scenario')) {
            return $generator->generateScenarioData($scenario);
        }

        // Auto-generate based on rule
        return $generator->generateAutoData($rule);
    }

    /**
     * Display detailed test result
     */
    private function displayDetailedResult(array $result): void
    {
        $format = $this->option('format');

        switch ($format) {
            case 'json':
                $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;

            case 'detail':
                $this->displayDetailedOutput($result);
                break;

            default:
                $this->displayTableOutput($result);
        }
    }

    /**
     * Display result in table format
     */
    private function displayTableOutput(array $result): void
    {
        // Overall result
        $status = $result['success'] ? '<info>✅ Sucesso</info>' : '<error>❌ Falha</error>';
        $this->line("Status: {$status}");
        $this->line("Tempo de execução: {$result['execution_time']}ms");
        $this->newLine();

        // Conditions
        if (! empty($result['condition_results'])) {
            $this->line('<comment>🔍 Condições:</comment>');
            $conditionData = [];
            foreach ($result['condition_results'] as $i => $condition) {
                $conditionData[] = [
                    'Nº' => $i + 1,
                    'Expressão' => $condition['expression'],
                    'Resultado' => $condition['result'] ? '✅' : '❌',
                ];
            }
            $this->table(['Nº', 'Expressão', 'Resultado'], $conditionData);
            $this->newLine();
        }

        // Actions
        if (! empty($result['action_results'])) {
            $this->line('<comment>⚡ Ações:</comment>');
            $actionData = [];
            foreach ($result['action_results'] as $i => $action) {
                $actionData[] = [
                    'Nº' => $i + 1,
                    'Tipo' => $action['type'],
                    'Status' => $action['success'] ? '✅' : '❌',
                    'Detalhes' => substr($action['details'], 0, 50) . '...',
                ];
            }
            $this->table(['Nº', 'Tipo', 'Status', 'Detalhes'], $actionData);
        }
    }

    /**
     * Display detailed output
     */
    private function displayDetailedOutput(array $result): void
    {
        $this->line('<comment>📊 RESULTADO DETALHADO</comment>');
        $this->line(str_repeat('=', 50));

        // Summary
        $status = $result['success'] ? '<info>✅ Sucesso</info>' : '<error>❌ Falha</error>';
        $this->line("Status: {$status}");
        $this->line("Tempo de execução: {$result['execution_time']}ms");
        $this->line("Condições avaliadas: {$result['conditions_evaluated']}");
        $this->line("Ações executadas: {$result['actions_executed']}");
        $this->newLine();

        // Conditions detail
        if (! empty($result['condition_results'])) {
            $this->line('<comment>🔍 CONDIÇÕES:</comment>');
            foreach ($result['condition_results'] as $i => $condition) {
                $status = $condition['result'] ? '✅' : '❌';
                $this->line("  {$status} Condição " . ($i + 1) . ": {$condition['expression']}");
                if (isset($condition['details'])) {
                    $this->line("     {$condition['details']}");
                }
            }
            $this->newLine();
        }

        // Actions detail
        if (! empty($result['action_results'])) {
            $this->line('<comment>⚡ AÇÕES:</comment>');
            foreach ($result['action_results'] as $i => $action) {
                $status = $action['success'] ? '✅' : '❌';
                $this->line("  {$status} Ação " . ($i + 1) . " ({$action['type']})");
                $this->line("     {$action['details']}");
            }
            $this->newLine();
        }

        // Debug log
        if ($this->option('detailed') && ! empty($result['debug_log'])) {
            $this->line('<comment>🐛 LOG DE DEBUG:</comment>');
            foreach ($result['debug_log'] as $entry) {
                $level = match ($entry['level']) {
                    'ERROR' => '<error>ERROR</error>',
                    'WARN' => '<comment>WARN</comment>',
                    'INFO' => '<info>INFO</info>',
                    default => $entry['level'],
                };
                $this->line("  [{$entry['timestamp']}] {$level}: {$entry['message']}");
            }
        }
    }

    /**
     * Display test summary
     */
    private function displaySummary(int $passed, int $failed, array $results): void
    {
        $total = $passed + $failed;

        $this->info("📊 RESUMO DOS TESTES");
        $this->line("Total de regras testadas: {$total}");
        $this->line("✅ Sucessos: {$passed}");
        $this->line("❌ Falhas: {$failed}");

        if ($failed > 0) {
            $this->newLine();
            $this->warn("Regras com falhas:");
            foreach ($results as $test) {
                if (! $test['result']['success']) {
                    $rule = $test['rule'];
                    $error = $test['result']['error'] ?? 'Erro desconhecido';
                    $this->line("• {$rule->name} (ID: {$rule->id}): {$error}");
                }
            }
        }

        $successRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        $this->newLine();
        $this->line("Taxa de sucesso: {$successRate}%");
    }
}