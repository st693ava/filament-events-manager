<?php

namespace St693ava\FilamentEventsManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Services\OptimizedRuleEngine;
use St693ava\FilamentEventsManager\Support\EventContext;

class ProcessRuleActionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public int $timeout = 300; // 5 minutes

    public function __construct(
        private EventRule $rule,
        private array $data,
        private array $contextData
    ) {
        $this->onQueue(config('filament-events-manager.queue_name', 'default'));
    }

    /**
     * Execute the job
     */
    public function handle(OptimizedRuleEngine $ruleEngine): void
    {
        try {
            Log::debug('Processing rule actions job', [
                'rule_id' => $this->rule->id,
                'rule_name' => $this->rule->name,
            ]);

            // Recreate EventContext from array
            $context = EventContext::fromArray($this->contextData);

            // Process the rule
            $ruleEngine->processRule($this->rule, $this->data, $context);

            Log::info('Rule actions job completed successfully', [
                'rule_id' => $this->rule->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Rule actions job failed', [
                'rule_id' => $this->rule->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Rule actions job failed permanently', [
            'rule_id' => $this->rule->id,
            'rule_name' => $this->rule->name,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // You could send notifications, create alerts, etc.
        // For now, we'll just log the failure
    }

    /**
     * Calculate retry delay
     */
    public function backoff(): array
    {
        return [1, 5, 10]; // Wait 1, 5, then 10 seconds between retries
    }

    /**
     * Determine if the job should be retried based on the exception
     */
    public function shouldRetry(\Throwable $exception): bool
    {
        // Don't retry for certain types of exceptions
        $nonRetryableExceptions = [
            \InvalidArgumentException::class,
            \BadMethodCallException::class,
        ];

        foreach ($nonRetryableExceptions as $exceptionClass) {
            if ($exception instanceof $exceptionClass) {
                return false;
            }
        }

        return true;
    }
}