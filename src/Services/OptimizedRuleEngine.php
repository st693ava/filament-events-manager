<?php

namespace St693ava\FilamentEventsManager\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use St693ava\FilamentEventsManager\Actions\ActionManager;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventLog;
use St693ava\FilamentEventsManager\Support\EventContext;
use St693ava\FilamentEventsManager\Jobs\ProcessRuleActionsJob;

class OptimizedRuleEngine
{
    public function __construct(
        private ConditionEvaluator $conditionEvaluator,
        private ActionManager $actionManager,
        private ContextCollector $contextCollector,
        private RuleCacheManager $cacheManager
    ) {}

    /**
     * Process event with optimized rule matching and caching
     */
    public function processEvent(string $eventName, array $data, ?EventContext $context = null): void
    {
        $startTime = microtime(true);

        try {
            // Collect context if not provided
            if (!$context) {
                $context = $this->contextCollector->collect($eventName, $data);
            }

            // Get matching rules with caching
            $rules = $this->getMatchingRulesOptimized($eventName);

            if ($rules->isEmpty()) {
                return;
            }

            Log::debug('Processing event', [
                'event_name' => $eventName,
                'rules_count' => $rules->count(),
            ]);

            // Process rules in parallel if configured
            if (config('filament-events-manager.async_processing', false)) {
                $this->processRulesAsync($rules, $data, $context);
            } else {
                $this->processRulesSync($rules, $data, $context, $startTime);
            }

        } catch (\Exception $e) {
            Log::error('Event processing failed', [
                'event_name' => $eventName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Process a single rule (used by both sync and async processing)
     */
    public function processRule(EventRule $rule, array $data, EventContext $context, float $startTime = null): void
    {
        $ruleStartTime = $startTime ?? microtime(true);

        try {
            Log::debug('Processing rule', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
            ]);

            // Evaluate conditions with optimized caching
            if (!$this->evaluateConditionsOptimized($rule, $data, $context)) {
                Log::debug('Rule conditions not met', ['rule_id' => $rule->id]);
                return;
            }

            // Execute actions with error handling
            $actionsExecuted = $this->executeActionsOptimized($rule, $data, $context);

            // Log successful execution
            $this->logExecution($rule, $data, $context, $actionsExecuted, $ruleStartTime, true);

            Log::info('Rule executed successfully', [
                'rule_id' => $rule->id,
                'actions_count' => count($actionsExecuted),
                'execution_time' => round((microtime(true) - $ruleStartTime) * 1000, 2) . 'ms',
            ]);

        } catch (\Exception $e) {
            // Log failed execution
            $this->logExecution($rule, $data, $context, [], $ruleStartTime, false, $e);

            Log::error('Rule execution failed', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get matching rules with optimized caching
     */
    private function getMatchingRulesOptimized(string $eventName): \Illuminate\Database\Eloquent\Collection
    {
        // Determine trigger type from event name
        $triggerType = $this->determineTriggerType($eventName);

        // Get rules by trigger type (cached)
        $rules = $this->cacheManager->getRulesByTriggerType($triggerType);

        // Filter rules that match the specific event
        return $rules->filter(function (EventRule $rule) use ($eventName, $triggerType) {
            return $this->ruleMatchesEvent($rule, $eventName, $triggerType);
        });
    }

    /**
     * Determine trigger type from event name
     */
    private function determineTriggerType(string $eventName): string
    {
        if (str_starts_with($eventName, 'eloquent.')) {
            return 'eloquent';
        }

        if (str_starts_with($eventName, 'sql.')) {
            return 'sql';
        }

        if (str_starts_with($eventName, 'schedule.')) {
            return 'schedule';
        }

        return 'custom';
    }

    /**
     * Check if rule matches event
     */
    private function ruleMatchesEvent(EventRule $rule, string $eventName, string $triggerType): bool
    {
        $config = $rule->trigger_config ?? [];

        switch ($triggerType) {
            case 'eloquent':
                return $this->matchesEloquentEvent($eventName, $config);

            case 'sql':
                return $this->matchesSqlEvent($eventName, $config);

            case 'schedule':
                return $this->matchesScheduleEvent($eventName, $config);

            case 'custom':
                return $this->matchesCustomEvent($eventName, $config);

            default:
                return false;
        }
    }

    /**
     * Check if event matches Eloquent trigger configuration
     */
    private function matchesEloquentEvent(string $eventName, array $config): bool
    {
        if (!str_starts_with($eventName, 'eloquent.')) {
            return false;
        }

        // Extract event type and model from event name
        // Format: "eloquent.created: App\Models\User"
        [$eventPart, $modelPart] = explode(': ', $eventName, 2);
        $eventType = str_replace('eloquent.', '', $eventPart);

        // Check if event type matches
        $allowedEvents = $config['events'] ?? [];
        if (!empty($allowedEvents) && !in_array($eventType, $allowedEvents)) {
            return false;
        }

        // Check if model matches
        $allowedModel = $config['model'] ?? null;
        if ($allowedModel && $allowedModel !== $modelPart) {
            return false;
        }

        return true;
    }

    /**
     * Check if event matches SQL trigger configuration
     */
    private function matchesSqlEvent(string $eventName, array $config): bool
    {
        // Implementation for SQL event matching
        return str_starts_with($eventName, 'sql.');
    }

    /**
     * Check if event matches Schedule trigger configuration
     */
    private function matchesScheduleEvent(string $eventName, array $config): bool
    {
        // Implementation for schedule event matching
        return str_starts_with($eventName, 'schedule.');
    }

    /**
     * Check if event matches Custom trigger configuration
     */
    private function matchesCustomEvent(string $eventName, array $config): bool
    {
        $eventClass = $config['event_class'] ?? null;

        if (!$eventClass) {
            return false;
        }

        // For custom events, the event name might be the class name
        return $eventName === $eventClass || str_contains($eventName, $eventClass);
    }

    /**
     * Process rules synchronously
     */
    private function processRulesSync($rules, array $data, EventContext $context, float $startTime): void
    {
        foreach ($rules as $rule) {
            $this->processRule($rule, $data, $context, $startTime);
        }
    }

    /**
     * Process rules asynchronously
     */
    private function processRulesAsync($rules, array $data, EventContext $context): void
    {
        foreach ($rules as $rule) {
            // Dispatch job to process rule actions
            ProcessRuleActionsJob::dispatch($rule, $data, $context->toArray())
                ->onQueue(config('filament-events-manager.queue_name', 'default'));
        }
    }

    /**
     * Evaluate conditions with optimized caching
     */
    private function evaluateConditionsOptimized(EventRule $rule, array $data, EventContext $context): bool
    {
        if (!$rule->conditions || $rule->conditions->isEmpty()) {
            return true; // No conditions means always execute
        }

        // Use cached conditions
        $conditions = $this->cacheManager->getRuleConditions($rule->id);

        if ($conditions->isEmpty()) {
            return true;
        }

        return $this->conditionEvaluator->evaluate($conditions, $data, $context);
    }

    /**
     * Execute actions with optimization and error handling
     */
    private function executeActionsOptimized(EventRule $rule, array $data, EventContext $context): array
    {
        $results = [];

        // Use cached actions
        $actions = $this->cacheManager->getRuleActions($rule->id);

        if ($actions->isEmpty()) {
            return $results;
        }

        // Group actions by priority for better execution order
        $actionGroups = $actions->groupBy('priority');

        foreach ($actionGroups as $priority => $priorityActions) {
            $groupResults = $this->executeActionGroup($priorityActions, $data, $context);
            $results = array_merge($results, $groupResults);

            // Check if we should stop execution on critical errors
            $criticalFailures = collect($groupResults)->where('status', 'failed')
                ->where('critical', true);

            if ($criticalFailures->isNotEmpty() &&
                config('filament-events-manager.stop_on_critical_failure', false)) {
                Log::warning('Stopping rule execution due to critical failure', [
                    'rule_id' => $rule->id,
                    'failures' => $criticalFailures->count(),
                ]);
                break;
            }
        }

        return $results;
    }

    /**
     * Execute a group of actions with the same priority
     */
    private function executeActionGroup($actions, array $data, EventContext $context): array
    {
        $results = [];

        foreach ($actions as $action) {
            try {
                $result = $this->actionManager->execute($action, $data, $context);
                $results[] = array_merge($result, [
                    'action_id' => $action->id,
                    'action_type' => $action->action_type,
                    'status' => 'success',
                    'priority' => $action->priority,
                ]);

            } catch (\Exception $e) {
                $results[] = [
                    'action_id' => $action->id,
                    'action_type' => $action->action_type,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'priority' => $action->priority,
                    'critical' => $action->action_config['critical'] ?? false,
                ];

                Log::error('Action execution failed', [
                    'action_id' => $action->id,
                    'action_type' => $action->action_type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Log rule execution with optimizations
     */
    private function logExecution(
        EventRule $rule,
        array $data,
        EventContext $context,
        array $actionsExecuted,
        float $startTime,
        bool $success,
        ?\Exception $exception = null
    ): void {
        $executionTime = (microtime(true) - $startTime) * 1000; // in milliseconds

        // Extract model information if available
        $modelType = null;
        $modelId = null;

        foreach ($data as $item) {
            if ($item instanceof \Illuminate\Database\Eloquent\Model) {
                $modelType = get_class($item);
                $modelId = $item->getKey();
                break;
            }
        }

        // Create log entry
        $logData = [
            'event_rule_id' => $rule->id,
            'trigger_type' => $rule->trigger_type,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'event_name' => $context->getEventName(),
            'context' => $exception ? [] : $context->toArray(), // Minimize context on errors
            'actions_executed' => $exception ? [] : $actionsExecuted,
            'execution_time_ms' => (int) round($executionTime),
            'triggered_at' => $context->getTriggeredAt(),
            'user_id' => $context->getUserId(),
            'user_name' => $context->getUserName(),
            'ip_address' => $context->getIpAddress(),
            'user_agent' => $context->getUserAgent(),
            'request_url' => $context->getRequestUrl(),
            'request_method' => $context->getRequestMethod(),
            'session_id' => $context->getSessionId(),
        ];

        // Add error information if present
        if ($exception) {
            $logData['error_message'] = $exception->getMessage();
            $logData['error_file'] = $exception->getFile();
            $logData['error_line'] = $exception->getLine();
        }

        EventLog::create($logData);
    }

    /**
     * Clear all caches
     */
    public function clearCache(): void
    {
        $this->cacheManager->clearAllCache();
        Log::info('Rule engine cache cleared');
    }
}