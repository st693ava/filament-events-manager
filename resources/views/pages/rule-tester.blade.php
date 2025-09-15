<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Main form -->
        <x-filament::section>
            <x-slot name="heading">
                Configuração do Teste
            </x-slot>

            <x-slot name="description">
                Configure os parâmetros para testar uma regra de evento com dados simulados.
            </x-slot>

            {{ $this->form }}
        </x-filament::section>

        <!-- Helper info -->
        <x-filament::section>
            <x-slot name="heading">
                Como Usar o Testador
            </x-slot>

            <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Modo Dry-Run</h4>
                        <p>Simula a execução sem executar ações reais. Recomendado para testes seguros.</p>

                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Logging Verboso</h4>
                        <p>Ativa logs detalhados para debug e análise do processo de avaliação.</p>
                    </div>

                    <div class="space-y-2">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Tipos de Dados</h4>
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Automático:</strong> Gera dados baseados na configuração da regra</li>
                            <li><strong>Personalizado:</strong> Use seus próprios dados JSON</li>
                            <li><strong>Realísticos:</strong> Dados que simulam casos reais</li>
                            <li><strong>Casos Extremos:</strong> Valores nulos, vazios, muito grandes</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100">💡 Dica</h4>
                    <p class="text-blue-700 dark:text-blue-300">
                        Use placeholders nos templates das ações como <code>@{{ model.nome }}</code> ou <code>@{{ user.email }}</code>
                        para testar a renderização dinâmica de dados.
                    </p>
                </div>
            </div>
        </x-filament::section>

        <!-- Quick scenarios -->
        <x-filament::section>
            <x-slot name="heading">
                Cenários Rápidos
            </x-slot>

            <div class="grid md:grid-cols-3 gap-4">
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <h4 class="font-medium mb-2">🔔 Registo de Utilizador</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Simula a criação de uma nova conta de utilizador.
                    </p>
                    <x-filament::button
                        size="sm"
                        color="gray"
                        wire:click="loadScenario('user_registration')"
                    >
                        Carregar Cenário
                    </x-filament::button>
                </div>

                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <h4 class="font-medium mb-2">🛒 Encomenda Criada</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Simula a criação de uma nova encomenda.
                    </p>
                    <x-filament::button
                        size="sm"
                        color="gray"
                        wire:click="loadScenario('order_created')"
                    >
                        Carregar Cenário
                    </x-filament::button>
                </div>

                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <h4 class="font-medium mb-2">📦 Produto Atualizado</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Simula a atualização de informações de produto.
                    </p>
                    <x-filament::button
                        size="sm"
                        color="gray"
                        wire:click="loadScenario('product_updated')"
                    >
                        Carregar Cenário
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    </div>

    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('download-scenario', (scenario) => {
                const blob = new Blob([scenario], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `test-scenario-${new Date().getTime()}.json`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            });
        });
    </script>
</x-filament-panels::page>