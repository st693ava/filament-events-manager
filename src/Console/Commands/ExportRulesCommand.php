<?php

namespace St693ava\FilamentEventsManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use St693ava\FilamentEventsManager\Services\RuleImportExportManager;

class ExportRulesCommand extends Command
{
    protected $signature = 'events:export-rules
                            {--file= : Output file path}
                            {--rules= : Comma-separated rule IDs to export}
                            {--include-ids : Include rule IDs in export}
                            {--include-timestamps : Include timestamps in export}
                            {--format=json : Output format (json)}';

    protected $description = 'Export event rules to JSON file';

    private RuleImportExportManager $importExportManager;

    public function __construct(RuleImportExportManager $importExportManager)
    {
        parent::__construct();
        $this->importExportManager = $importExportManager;
    }

    public function handle(): int
    {
        $this->info('📤 Exporting event rules...');

        try {
            // Parse rule IDs if provided
            $ruleIds = null;
            if ($rulesOption = $this->option('rules')) {
                $ruleIds = array_map('intval', explode(',', $rulesOption));
                $this->info("Exporting specific rules: " . implode(', ', $ruleIds));
            } else {
                $this->info('Exporting all rules');
            }

            // Set export options
            $options = [
                'include_ids' => $this->option('include-ids'),
                'include_timestamps' => $this->option('include-timestamps'),
            ];

            // Export rules
            $data = $this->importExportManager->exportRules($ruleIds, $options);

            // Determine output file
            $filePath = $this->option('file');
            if (!$filePath) {
                $timestamp = now()->format('Y-m-d_H-i-s');
                $filePath = "event-rules-export-{$timestamp}.json";
            }

            // Ensure directory exists
            $directory = dirname($filePath);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Write to file
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            File::put($filePath, $json);

            $this->newLine();
            $this->info("✅ Export completed successfully!");
            $this->line("📄 File: {$filePath}");
            $this->line("📊 Rules exported: {$data['total_rules']}");
            $this->line("📅 Exported at: {$data['exported_at']}");

            // Show statistics
            $this->displayStatistics($data);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Export failed: {$e->getMessage()}");

            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    private function displayStatistics(array $data): void
    {
        if (empty($data['rules'])) {
            return;
        }

        $this->newLine();
        $this->info('📈 Export Statistics:');

        // Count by trigger type
        $triggerTypes = [];
        $totalConditions = 0;
        $totalActions = 0;

        foreach ($data['rules'] as $rule) {
            $triggerType = $rule['trigger_type'];
            $triggerTypes[$triggerType] = ($triggerTypes[$triggerType] ?? 0) + 1;
            $totalConditions += count($rule['conditions']);
            $totalActions += count($rule['actions']);
        }

        foreach ($triggerTypes as $type => $count) {
            $this->line("  • {$type}: {$count} rules");
        }

        $this->line("  • Total conditions: {$totalConditions}");
        $this->line("  • Total actions: {$totalActions}");
    }
}