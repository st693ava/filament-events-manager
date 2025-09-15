<?php

namespace St693ava\FilamentEventsManager\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Support\EventContext;

class RuleTestRunner
{
    private bool $dryRun = true;
    private bool $verboseLogging = false;
    private array $debugLog = [];
    private ConditionEvaluator $conditionEvaluator;
    private TemplateRenderer $templateRenderer;

    public function __construct()
    {
        $this->conditionEvaluator = new ConditionEvaluator();
        $this->templateRenderer = new TemplateRenderer();
    }

    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;
        return $this;
    }

    public function setVerboseLogging(bool $verbose): self
    {
        $this->verboseLogging = $verbose;
        return $this;
    }

    /**
     * Test a rule with mock data
     */
    public function testRule(EventRule $rule, array $mockData): array
    {
        $startTime = microtime(true);
        $this->debugLog = [];

        $this->log('info', "Iniciando teste da regra: {$rule->name} (ID: {$rule->id})");
        $this->log('debug', "Modo Dry-Run: " . ($this->dryRun ? 'Sim' : 'Não'));
        $this->log('debug', "Dados de entrada: " . json_encode($mockData, JSON_UNESCAPED_UNICODE));

        try {
            // Create event context
            $context = $this->createTestContext($rule, $mockData);
            $this->log('debug', "Contexto do evento criado");

            // Evaluate conditions
            $conditionResults = $this->evaluateConditions($rule, $mockData, $context);
            $conditionsPass = $this->determineOverallConditionResult($conditionResults);

            $this->log('info', "Avaliação de condições: " . ($conditionsPass ? 'PASSOU' : 'FALHOU'));

            // Execute actions if conditions pass
            $actionResults = [];
            if ($conditionsPass) {
                $actionResults = $this->executeActions($rule, $mockData, $context);
            } else {
                $this->log('info', "Ações não executadas - condições não foram atendidas");
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => $conditionsPass,
                'execution_time' => $executionTime,
                'conditions_evaluated' => count($conditionResults),
                'actions_executed' => count($actionResults),
                'condition_results' => $conditionResults,
                'action_results' => $actionResults,
                'debug_log' => $this->debugLog,
                'mock_data' => $mockData,
                'context' => $context->all(),
            ];

        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->log('error', "Erro durante execução: {$e->getMessage()}");

            return [
                'success' => false,
                'execution_time' => $executionTime,
                'conditions_evaluated' => 0,
                'actions_executed' => 0,
                'condition_results' => [],
                'action_results' => [],
                'error' => $e->getMessage(),
                'debug_log' => $this->debugLog,
                'mock_data' => $mockData,
            ];
        }
    }

    /**
     * Create test event context
     */
    private function createTestContext(EventRule $rule, array $mockData): EventContext
    {
        $context = new EventContext();

        // Set basic context
        $context->set('event_name', 'test_event');
        $context->set('model_type', $rule->trigger_config['model'] ?? 'TestModel');
        $context->set('triggered_at', now()->toISOString());
        $context->set('is_test', true);
        $context->set('dry_run', $this->dryRun);

        // Set user context (mock)
        $context->set('user_id', 1);
        $context->set('user_name', 'Test User');
        $context->set('user_email', 'test@example.com');

        // Set request context (mock)
        $context->set('ip_address', '127.0.0.1');
        $context->set('user_agent', 'Test Runner');
        $context->set('request_url', '/test');
        $context->set('request_method', 'POST');

        $this->log('debug', "Contexto criado com " . count($context->all()) . " propriedades");

        return $context;
    }

    /**
     * Evaluate all conditions for the rule
     */
    private function evaluateConditions(EventRule $rule, array $mockData, EventContext $context): array
    {
        $conditions = $rule->conditions()->orderBy('priority', 'desc')->orderBy('sort_order')->get();

        if ($conditions->isEmpty()) {
            $this->log('info', "Nenhuma condição definida - regra sempre executará");
            return [];
        }

        $this->log('debug', "Avaliando " . $conditions->count() . " condições");

        $results = [];

        foreach ($conditions as $condition) {
            try {
                // Criar uma collection com apenas esta condição para testar individualmente
                $singleCondition = collect([$condition]);
                $result = $this->conditionEvaluator->evaluate($singleCondition, $mockData, $context);

                $expression = "{$condition->field_path} {$condition->operator} " .
                    (is_array($condition->value) ? json_encode($condition->value) : $condition->value);

                $results[] = [
                    'condition_id' => $condition->id,
                    'expression' => $expression,
                    'result' => $result,
                    'field_path' => $condition->field_path,
                    'operator' => $condition->operator,
                    'value' => $condition->value,
                    'logical_operator' => $condition->logical_operator,
                    'details' => $this->getConditionDetails($condition, $mockData, $result),
                ];

                $this->log('debug', "Condição '{$expression}': " . ($result ? 'VERDADEIRO' : 'FALSO'));

            } catch (Exception $e) {
                $this->log('error', "Erro ao avaliar condição {$condition->id}: {$e->getMessage()}");

                $results[] = [
                    'condition_id' => $condition->id,
                    'expression' => 'ERRO',
                    'result' => false,
                    'error' => $e->getMessage(),
                    'details' => "Erro: {$e->getMessage()}",
                ];
            }
        }

        return $results;
    }

    /**
     * Get detailed information about condition evaluation
     */
    private function getConditionDetails($condition, array $mockData, bool $result): string
    {
        $fieldResolver = new FieldPathResolver();
        $actualValue = $fieldResolver->resolve($condition->field_path, $mockData);

        $details = "Campo '{$condition->field_path}' = " . json_encode($actualValue, JSON_UNESCAPED_UNICODE);
        $details .= " | Comparação: " . json_encode($condition->value, JSON_UNESCAPED_UNICODE);
        $details .= " | Resultado: " . ($result ? 'Verdadeiro' : 'Falso');

        return $details;
    }

    /**
     * Determine overall result from individual condition results
     */
    private function determineOverallConditionResult(array $conditionResults): bool
    {
        if (empty($conditionResults)) {
            return true; // No conditions = always execute
        }

        // Simple implementation: evaluate based on logical operators
        // For complex expressions with parentheses, we'd need a more sophisticated parser

        $overallResult = true;
        $currentLogicalOp = 'AND';

        foreach ($conditionResults as $condition) {
            if ($currentLogicalOp === 'AND') {
                $overallResult = $overallResult && $condition['result'];
            } else { // OR
                $overallResult = $overallResult || $condition['result'];
            }

            $currentLogicalOp = $condition['logical_operator'] ?? 'AND';
        }

        return $overallResult;
    }

    /**
     * Execute all actions for the rule
     */
    private function executeActions(EventRule $rule, array $mockData, EventContext $context): array
    {
        $actions = $rule->actions()->where('is_active', true)->get();

        if ($actions->isEmpty()) {
            $this->log('info', "Nenhuma ação ativa definida");
            return [];
        }

        $this->log('info', "Executando " . $actions->count() . " ações");

        $results = [];

        foreach ($actions as $action) {
            try {
                if ($this->dryRun) {
                    $result = $this->simulateAction($action, $mockData, $context);
                } else {
                    $result = $this->executeRealAction($action, $mockData, $context);
                }

                $results[] = [
                    'action_id' => $action->id,
                    'type' => $action->action_type,
                    'success' => $result['success'] ?? true,
                    'details' => $result['details'] ?? 'Executado com sucesso',
                    'output' => $result,
                ];

                $this->log('info', "Ação {$action->action_type}: " . ($result['success'] ? 'SUCESSO' : 'FALHA'));

            } catch (Exception $e) {
                $this->log('error', "Erro ao executar ação {$action->id}: {$e->getMessage()}");

                $results[] = [
                    'action_id' => $action->id,
                    'type' => $action->action_type,
                    'success' => false,
                    'details' => "Erro: {$e->getMessage()}",
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Simulate action execution (dry-run mode)
     */
    private function simulateAction($action, array $mockData, EventContext $context): array
    {
        $this->log('debug', "Simulando ação {$action->action_type} (Dry-Run)");

        // Render templates to test template processing
        $renderedConfig = $this->renderActionConfig($action->action_config, $mockData, $context);

        return [
            'success' => true,
            'simulated' => true,
            'action_type' => $action->action_type,
            'original_config' => $action->action_config,
            'rendered_config' => $renderedConfig,
            'details' => "Simulação - configuração renderizada com sucesso",
        ];
    }

    /**
     * Execute action for real
     */
    private function executeRealAction($action, array $mockData, EventContext $context): array
    {
        $this->log('debug', "Executando ação real {$action->action_type}");

        // In a real implementation, this would use the ActionManager
        // For now, we'll just simulate but mark as real execution
        return [
            'success' => true,
            'simulated' => false,
            'action_type' => $action->action_type,
            'details' => "Execução real - ação seria executada aqui",
        ];
    }

    /**
     * Render action configuration with templates
     */
    private function renderActionConfig(array $config, array $mockData, EventContext $context): array
    {
        $rendered = [];

        foreach ($config as $key => $value) {
            if (is_string($value)) {
                $rendered[$key] = $this->templateRenderer->render($value, $mockData, $context);
            } elseif (is_array($value)) {
                $rendered[$key] = $this->renderActionConfig($value, $mockData, $context);
            } else {
                $rendered[$key] = $value;
            }
        }

        return $rendered;
    }

    /**
     * Log debug information
     */
    private function log(string $level, string $message): void
    {
        $logEntry = [
            'timestamp' => now()->format('H:i:s.u'),
            'level' => strtoupper($level),
            'message' => $message,
        ];

        $this->debugLog[] = $logEntry;

        if ($this->verboseLogging) {
            Log::channel('single')->{$level}("[RuleTestRunner] {$message}");
        }
    }

    /**
     * Get the complete debug log
     */
    public function getDebugLog(): array
    {
        return $this->debugLog;
    }

    /**
     * Validate a rule configuration without executing it
     */
    public function validateRule(EventRule $rule): array
    {
        $errors = [];
        $warnings = [];

        // Validate trigger configuration
        if (empty($rule->trigger_config)) {
            $errors[] = "Configuração de trigger está vazia";
        }

        if ($rule->trigger_type === 'eloquent') {
            $modelClass = $rule->trigger_config['model'] ?? null;
            if (! $modelClass) {
                $errors[] = "Classe do modelo não definida para trigger eloquent";
            } elseif (! class_exists($modelClass)) {
                $errors[] = "Classe do modelo '{$modelClass}' não existe";
            }
        }

        // Validate conditions
        $conditions = $rule->conditions;
        if ($conditions->isNotEmpty()) {
            foreach ($conditions as $condition) {
                if (empty($condition->field_path)) {
                    $errors[] = "Campo vazio na condição {$condition->id}";
                }

                if (empty($condition->operator)) {
                    $errors[] = "Operador vazio na condição {$condition->id}";
                }

                if ($condition->operator !== 'changed' && empty($condition->value)) {
                    $warnings[] = "Valor vazio na condição {$condition->id} com operador {$condition->operator}";
                }
            }
        }

        // Validate actions
        $actions = $rule->actions;
        if ($actions->isEmpty()) {
            $warnings[] = "Nenhuma ação definida - regra não fará nada quando executada";
        } else {
            foreach ($actions as $action) {
                if (empty($action->action_config)) {
                    $errors[] = "Configuração vazia na ação {$action->id}";
                }

                // Validate specific action types
                if ($action->action_type === 'email') {
                    $config = $action->action_config;
                    if (empty($config['to'])) {
                        $errors[] = "Campo 'to' obrigatório na ação de email {$action->id}";
                    }
                    if (empty($config['subject'])) {
                        $errors[] = "Campo 'subject' obrigatório na ação de email {$action->id}";
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}