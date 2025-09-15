<?php

namespace St693ava\FilamentEventsManager\Services;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Support\EventContext;

class ScheduleTriggerManager
{
    private RuleEngine $ruleEngine;

    public function __construct(RuleEngine $ruleEngine)
    {
        $this->ruleEngine = $ruleEngine;
    }

    /**
     * Register all schedule-based triggers with Laravel Scheduler
     */
    public function registerScheduledTriggers(Schedule $schedule): void
    {
        try {
            $rules = EventRule::where('is_active', true)
                ->where('trigger_type', 'schedule')
                ->get();

            foreach ($rules as $rule) {
                $this->registerRuleSchedule($schedule, $rule);
            }

        } catch (\Exception $e) {
            Log::error('Failed to register scheduled triggers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Register a single rule schedule
     */
    private function registerRuleSchedule(Schedule $schedule, EventRule $rule): void
    {
        $config = $rule->trigger_config ?? [];

        if (empty($config['cron'])) {
            Log::warning('Schedule rule missing cron expression', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
            ]);
            return;
        }

        try {
            $command = $schedule->call(function () use ($rule) {
                $this->executeScheduledRule($rule);
            })->name("events_rule_{$rule->id}");

            // Apply cron expression
            $command->cron($config['cron']);

            // Apply additional schedule configuration
            $this->applyScheduleConfiguration($command, $config);

            Log::info('Scheduled rule registered', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'cron' => $config['cron'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to register rule schedule', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Execute a scheduled rule
     */
    public function executeScheduledRule(EventRule $rule): void
    {
        try {
            Log::info('Executing scheduled rule', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
            ]);

            // Create schedule context
            $context = $this->createScheduleContext($rule);

            // Generate mock data for the schedule execution
            $data = $this->generateScheduleData($rule);

            // Process the rule
            $this->ruleEngine->processRule($rule, $data, $context);

            Log::info('Scheduled rule executed successfully', [
                'rule_id' => $rule->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Scheduled rule execution failed', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Apply additional schedule configuration
     */
    private function applyScheduleConfiguration($command, array $config): void
    {
        // Timezone
        if (isset($config['timezone'])) {
            $command->timezone($config['timezone']);
        }

        // Environment restrictions
        if (isset($config['environments'])) {
            $environments = is_array($config['environments'])
                ? $config['environments']
                : [$config['environments']];
            $command->environments($environments);
        }

        // Prevent overlaps
        if (isset($config['without_overlapping']) && $config['without_overlapping']) {
            $command->withoutOverlapping($config['overlap_timeout'] ?? 1440); // Default 24 hours
        }

        // Run in background
        if (isset($config['run_in_background']) && $config['run_in_background']) {
            $command->runInBackground();
        }

        // Run on one server only
        if (isset($config['on_one_server']) && $config['on_one_server']) {
            $command->onOneServer();
        }

        // Email output
        if (isset($config['email_output_to'])) {
            $emails = is_array($config['email_output_to'])
                ? $config['email_output_to']
                : [$config['email_output_to']];
            $command->emailOutputTo($emails);
        }

        // Ping URLs before/after
        if (isset($config['ping_before'])) {
            $command->pingBefore($config['ping_before']);
        }

        if (isset($config['ping_after'])) {
            $command->thenPing($config['ping_after']);
        }

        // Truth test (only run if condition is true)
        if (isset($config['when_condition'])) {
            $command->when(function () use ($config) {
                return $this->evaluateCondition($config['when_condition']);
            });
        }

        // Skip condition (don't run if condition is true)
        if (isset($config['skip_condition'])) {
            $command->skip(function () use ($config) {
                return $this->evaluateCondition($config['skip_condition']);
            });
        }
    }

    /**
     * Create event context for scheduled execution
     */
    private function createScheduleContext(EventRule $rule): EventContext
    {
        $context = new EventContext();

        $context->set('event_type', 'schedule');
        $context->set('rule_id', $rule->id);
        $context->set('rule_name', $rule->name);
        $context->set('trigger_type', 'schedule');
        $context->set('triggered_at', now()->toISOString());

        // Schedule-specific context
        $config = $rule->trigger_config ?? [];
        $context->set('cron_expression', $config['cron'] ?? null);
        $context->set('timezone', $config['timezone'] ?? config('app.timezone'));

        // System context
        $context->set('php_version', PHP_VERSION);
        $context->set('laravel_version', app()->version());
        $context->set('environment', app()->environment());
        $context->set('server_time', now()->format('Y-m-d H:i:s'));

        return $context;
    }

    /**
     * Generate data for scheduled execution
     */
    private function generateScheduleData(EventRule $rule): array
    {
        $config = $rule->trigger_config ?? [];

        // If custom data is provided in config, use it
        if (isset($config['data'])) {
            return $config['data'];
        }

        // Otherwise generate basic schedule data
        return [
            'scheduled_at' => now()->toISOString(),
            'rule_id' => $rule->id,
            'execution_type' => 'scheduled',
            'cron_expression' => $config['cron'] ?? null,
        ];
    }

    /**
     * Evaluate a condition for schedule constraints
     */
    private function evaluateCondition(string $condition): bool
    {
        try {
            // This is a simplified condition evaluator for schedule constraints
            // In a real implementation, you might want to integrate with your
            // existing ConditionEvaluator or create a specific schedule condition evaluator

            // For now, support some basic conditions
            if ($condition === 'app.debug') {
                return config('app.debug');
            }

            if ($condition === 'env.production') {
                return app()->environment('production');
            }

            if (strpos($condition, 'time.') === 0) {
                return $this->evaluateTimeCondition(substr($condition, 5));
            }

            // Default to false for unknown conditions
            return false;

        } catch (\Exception $e) {
            Log::error('Schedule condition evaluation failed', [
                'condition' => $condition,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Evaluate time-based conditions
     */
    private function evaluateTimeCondition(string $timeCondition): bool
    {
        $now = now();

        switch ($timeCondition) {
            case 'is_weekend':
                return $now->isWeekend();

            case 'is_weekday':
                return $now->isWeekday();

            case 'is_business_hours':
                return $now->hour >= 9 && $now->hour <= 17;

            case 'is_night':
                return $now->hour >= 22 || $now->hour <= 6;

            default:
                return false;
        }
    }

    /**
     * Get all registered schedule rules
     */
    public function getScheduledRules()
    {
        return EventRule::where('is_active', true)
            ->where('trigger_type', 'schedule')
            ->get();
    }

    /**
     * Validate cron expression
     */
    public function validateCronExpression(string $cron): bool
    {
        // Basic validation for cron expression format
        $parts = explode(' ', trim($cron));

        if (count($parts) !== 5) {
            return false;
        }

        foreach ($parts as $part) {
            if (!$this->isValidCronPart($part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate individual cron expression part
     */
    private function isValidCronPart(string $part): bool
    {
        // Allow * for any value
        if ($part === '*') {
            return true;
        }

        // Allow ranges (e.g., 1-5)
        if (preg_match('/^\d+-\d+$/', $part)) {
            return true;
        }

        // Allow step values (e.g., */5, 1-10/2)
        if (preg_match('/^(\*|\d+-\d+|\d+)\/\d+$/', $part)) {
            return true;
        }

        // Allow lists (e.g., 1,3,5)
        if (preg_match('/^\d+(,\d+)*$/', $part)) {
            return true;
        }

        // Allow single numbers
        if (preg_match('/^\d+$/', $part)) {
            return true;
        }

        return false;
    }
}