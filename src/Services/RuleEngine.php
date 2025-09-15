<?php

namespace St693ava\FilamentEventsManager\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use St693ava\FilamentEventsManager\Actions\ActionManager;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventLog;
use St693ava\FilamentEventsManager\Support\EventContext;

class RuleEngine
{
    public function __construct(
        private ConditionEvaluator $conditionEvaluator,
        private ActionManager $actionManager,
        private ContextCollector $contextCollector
    ) {}

    public function processEvent(string $eventName, array $data, ?EventContext $context = null): void
    {
        $startTime = microtime(true);

        try {
            // Se não temos contexto, coletar
            if (!$context) {
                $context = $this->contextCollector->collect($eventName, $data);
            }

            $rules = $this->getMatchingRules($eventName);

            foreach ($rules as $rule) {
                $this->processRule($rule, $data, $context, $startTime);
            }
        } catch (\Exception $e) {
            Log::error('Erro no processamento de evento', [
                'event_name' => $eventName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function processRule(EventRule $rule, array $data, EventContext $context, float $startTime = null): void
    {
        $ruleStartTime = microtime(true);

        try {
            // Verificar se a regra tem condições e se são satisfeitas
            if (!$this->evaluateConditions($rule, $data, $context)) {
                return;
            }

            // Executar ações
            $actionsExecuted = $this->executeActions($rule, $data, $context);

            // Registar log de sucesso
            $this->logExecution($rule, $data, $context, $actionsExecuted, $ruleStartTime, true);
        } catch (\Exception $e) {
            // Registar log de erro
            $this->logExecution($rule, $data, $context, [], $ruleStartTime, false, $e);

            Log::error('Erro na execução da regra', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getMatchingRules(string $eventName): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "event_rules_{$eventName}";

        return Cache::remember($cacheKey, config('filament-events-manager.cache_duration', 300), function () use ($eventName) {
            return EventRule::active()
                ->with(['conditions', 'actions' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order');
                }])
                ->where(function ($query) use ($eventName) {
                    // Para eventos Eloquent
                    $query->where('trigger_type', 'eloquent')
                        ->whereJsonContains('trigger_config->events', $this->extractEventType($eventName));
                })
                ->orderByDesc('priority')
                ->get();
        });
    }

    private function extractEventType(string $eventName): string
    {
        // Converter "eloquent.created: App\Models\User" para "created"
        if (str_starts_with($eventName, 'eloquent.')) {
            $parts = explode(':', $eventName);
            $eventPart = str_replace('eloquent.', '', $parts[0]);
            return $eventPart;
        }

        return $eventName;
    }

    private function evaluateConditions(EventRule $rule, array $data, EventContext $context): bool
    {
        if (!$rule->hasConditions()) {
            return true; // Sem condições significa sempre executar
        }

        return $this->conditionEvaluator->evaluate($rule->conditions, $data, $context);
    }

    private function executeActions(EventRule $rule, array $data, EventContext $context): array
    {
        $results = [];

        foreach ($rule->actions as $action) {
            if (!$action->is_active) {
                continue;
            }

            try {
                $result = $this->actionManager->execute($action, $data, $context);
                $results[] = array_merge($result, [
                    'action_id' => $action->id,
                    'action_type' => $action->action_type,
                    'status' => 'success',
                ]);
            } catch (\Exception $e) {
                $results[] = [
                    'action_id' => $action->id,
                    'action_type' => $action->action_type,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                Log::error('Erro na execução da ação', [
                    'action_id' => $action->id,
                    'action_type' => $action->action_type,
                    'rule_id' => $rule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    private function logExecution(
        EventRule $rule,
        array $data,
        EventContext $context,
        array $actionsExecuted,
        float $startTime,
        bool $success,
        ?\Exception $exception = null
    ): void {
        $executionTime = (microtime(true) - $startTime) * 1000; // em milissegundos

        // Extrair informação do modelo se disponível
        $modelType = null;
        $modelId = null;

        foreach ($data as $item) {
            if ($item instanceof \Illuminate\Database\Eloquent\Model) {
                $modelType = get_class($item);
                $modelId = $item->getKey();
                break;
            }
        }

        EventLog::create([
            'event_rule_id' => $rule->id,
            'trigger_type' => $rule->trigger_type,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'event_name' => $context->getEventName(),
            'context' => $context->toArray(),
            'actions_executed' => $exception ? [] : $actionsExecuted,
            'execution_time_ms' => (int) $executionTime,
            'triggered_at' => $context->getTriggeredAt(),
            'user_id' => $context->getUserId(),
            'user_name' => $context->getUserName(),
            'ip_address' => $context->getIpAddress(),
            'user_agent' => $context->getUserAgent(),
            'request_url' => $context->getRequestUrl(),
            'request_method' => $context->getRequestMethod(),
            'session_id' => $context->getSessionId(),
        ]);
    }

    public function clearCache(): void
    {
        Cache::flush();
    }
}