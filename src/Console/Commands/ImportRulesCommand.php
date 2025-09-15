<?php

namespace St693ava\FilamentEventsManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use St693ava\FilamentEventsManager\Services\RuleImportExportManager;

class ImportRulesCommand extends Command
{
    protected $signature = 'events:import-rules
                            {file : Path to JSON file to import}
                            {--skip-existing : Skip rules that already exist}
                            {--update-existing : Update existing rules instead of skipping}
                            {--dry-run : Show what would be imported without actually importing}
                            {--verbose : Show detailed output}';

    protected $description = 'Import event rules from JSON file';

    private RuleImportExportManager $importExportManager;

    public function __construct(RuleImportExportManager $importExportManager)
    {
        parent::__construct();
        $this->importExportManager = $importExportManager;
    }

    public function handle(): int
    {
        $filePath = $this->argument('file');

        $this->info("📥 Importing event rules from: {$filePath}");

        try {
            // Check if file exists
            if (!File::exists($filePath)) {
                $this->error("File not found: {$filePath}");
                return self::FAILURE;
            }

            // Read and parse JSON
            $json = File::get($filePath);
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Invalid JSON file: " . json_last_error_msg());
                return self::FAILURE;
            }

            // Show import preview
            $this->showImportPreview($data);

            if ($this->option('dry-run')) {
                $this->warn('🏃 Dry-run mode - no changes will be made');
                return self::SUCCESS;
            }

            // Confirm import
            if (!$this->confirm('Proceed with import?')) {
                $this->info('Import cancelled');
                return self::SUCCESS;
            }

            // Set import options
            $options = [
                'skip_existing' => $this->option('skip-existing'),
                'update_existing' => $this->option('update-existing'),
            ];

            // Perform import
            $result = $this->importExportManager->importRules($data, $options);

            // Display results
            $this->displayImportResults($result);

            return $result['success'] ? self::SUCCESS : self::FAILURE;

        } catch (\Exception $e) {
            $this->error("❌ Import failed: {$e->getMessage()}");

            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    private function showImportPreview(array $data): void
    {
        $this->newLine();
        $this->info('📋 Import Preview:');

        $this->line("  Version: " . ($data['version'] ?? 'Unknown'));
        $this->line("  Total rules: " . count($data['rules'] ?? []));

        if (isset($data['exported_at'])) {
            $this->line("  Exported at: {$data['exported_at']}");
        }

        if (isset($data['exported_by'])) {
            $this->line("  Exported by: {$data['exported_by']}");
        }

        // Show rule summary
        if (!empty($data['rules'])) {
            $this->newLine();
            $this->info('📝 Rules to import:');

            $triggerTypes = [];
            foreach ($data['rules'] as $index => $rule) {
                $name = $rule['name'] ?? "Rule {$index}";
                $type = $rule['trigger_type'] ?? 'unknown';
                $active = $rule['is_active'] ?? true;
                $status = $active ? '✅' : '❌';

                $this->line("  {$status} {$name} ({$type})");

                $triggerTypes[$type] = ($triggerTypes[$type] ?? 0) + 1;
            }

            $this->newLine();
            $this->info('📊 Rules by trigger type:');
            foreach ($triggerTypes as $type => $count) {
                $this->line("  • {$type}: {$count}");
            }
        }
    }

    private function displayImportResults(array $result): void
    {
        $this->newLine();

        if ($result['success']) {
            $this->info('✅ Import completed successfully!');
        } else {
            $this->error('❌ Import completed with errors');
        }

        $this->line("📊 Results:");
        $this->line("  • Imported: {$result['imported']}");
        $this->line("  • Skipped: {$result['skipped']}");
        $this->line("  • Errors: " . count($result['errors']));

        // Show imported rules
        if (!empty($result['imported_rules'])) {
            $this->newLine();
            $this->info('✅ Successfully imported rules:');
            foreach ($result['imported_rules'] as $rule) {
                $this->line("  • {$rule->name} (ID: {$rule->id})");
            }
        }

        // Show errors
        if (!empty($result['errors'])) {
            $this->newLine();
            $this->error('❌ Errors encountered:');
            foreach ($result['errors'] as $error) {
                $this->line("  • {$error}");
            }
        }

        // Show detailed information if verbose
        if ($this->option('verbose') && !empty($result['imported_rules'])) {
            $this->newLine();
            $this->info('🔍 Detailed information:');
            foreach ($result['imported_rules'] as $rule) {
                $this->line("Rule: {$rule->name}");
                $this->line("  • Type: {$rule->trigger_type}");
                $this->line("  • Conditions: {$rule->conditions->count()}");
                $this->line("  • Actions: {$rule->actions->count()}");
                $this->line("  • Active: " . ($rule->is_active ? 'Yes' : 'No'));
                $this->newLine();
            }
        }
    }
}