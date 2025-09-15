<?php

namespace St693ava\FilamentEventsManager\Console\Commands;

use Illuminate\Console\Command;
use St693ava\FilamentEventsManager\Services\ScheduleTriggerManager;

class ProcessScheduledRulesCommand extends Command
{
    protected $signature = 'events:process-scheduled
                            {--rule= : Process specific rule by ID}
                            {--dry-run : Simulate execution without running real actions}
                            {--verbose : Enable verbose output}';

    protected $description = 'Process scheduled event rules';

    private ScheduleTriggerManager $scheduleManager;

    public function __construct(ScheduleTriggerManager $scheduleManager)
    {
        parent::__construct();
        $this->scheduleManager = $scheduleManager;
    }

    public function handle(): int
    {
        $this->info('🕐 Processing scheduled event rules...');

        if ($ruleId = $this->option('rule')) {
            return $this->processSpecificRule($ruleId);
        }

        return $this->processAllScheduledRules();
    }

    private function processSpecificRule(int $ruleId): int
    {
        $rule = \St693ava\FilamentEventsManager\Models\EventRule::find($ruleId);

        if (!$rule) {
            $this->error("Rule with ID {$ruleId} not found.");
            return self::FAILURE;
        }

        if ($rule->trigger_type !== 'schedule') {
            $this->error("Rule {$ruleId} is not a scheduled rule.");
            return self::FAILURE;
        }

        if (!$rule->is_active) {
            $this->warn("Rule {$ruleId} is not active.");
            return self::SUCCESS;
        }

        $this->info("Processing rule: {$rule->name} (ID: {$rule->id})");

        try {
            $this->scheduleManager->executeScheduledRule($rule);
            $this->info('✅ Rule executed successfully');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Rule execution failed: {$e->getMessage()}");
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }
            return self::FAILURE;
        }
    }

    private function processAllScheduledRules(): int
    {
        $rules = $this->scheduleManager->getScheduledRules();

        if ($rules->isEmpty()) {
            $this->warn('No scheduled rules found.');
            return self::SUCCESS;
        }

        $this->info("Found {$rules->count()} scheduled rules");

        $processed = 0;
        $failed = 0;

        foreach ($rules as $rule) {
            $this->line("Processing: {$rule->name} (ID: {$rule->id})");

            try {
                $this->scheduleManager->executeScheduledRule($rule);
                $this->info('  ✅ Success');
                $processed++;
            } catch (\Exception $e) {
                $this->error("  ❌ Failed: {$e->getMessage()}");
                $failed++;

                if ($this->option('verbose')) {
                    $this->line("    " . $e->getTraceAsString());
                }
            }
        }

        $this->newLine();
        $this->info("📊 Summary:");
        $this->line("  Processed: {$processed}");
        $this->line("  Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}