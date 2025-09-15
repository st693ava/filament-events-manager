<?php

namespace St693ava\FilamentEventsManager\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventRuleCondition;
use St693ava\FilamentEventsManager\Models\EventRuleAction;

class RuleImportExportManager
{
    /**
     * Export rules to JSON format
     */
    public function exportRules(array $ruleIds = null, array $options = []): array
    {
        try {
            $query = EventRule::with(['conditions', 'actions']);

            if ($ruleIds) {
                $query->whereIn('id', $ruleIds);
            }

            $rules = $query->get();

            $exportData = [
                'version' => '2.0.0',
                'exported_at' => now()->toISOString(),
                'exported_by' => auth()->user()?->name ?? 'System',
                'total_rules' => $rules->count(),
                'rules' => [],
            ];

            foreach ($rules as $rule) {
                $ruleData = $this->serializeRule($rule, $options);
                $exportData['rules'][] = $ruleData;
            }

            Log::info('Rules exported successfully', [
                'total_rules' => $rules->count(),
                'rule_ids' => $ruleIds,
            ]);

            return $exportData;

        } catch (\Exception $e) {
            Log::error('Rule export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Export rules to JSON string
     */
    public function exportRulesToJson(array $ruleIds = null, array $options = []): string
    {
        $data = $this->exportRules($ruleIds, $options);
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Import rules from JSON data
     */
    public function importRules(array $data, array $options = []): array
    {
        $results = [
            'success' => true,
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
            'imported_rules' => [],
        ];

        try {
            // Validate import data structure
            $this->validateImportData($data);

            DB::beginTransaction();

            foreach ($data['rules'] as $index => $ruleData) {
                try {
                    $result = $this->importSingleRule($ruleData, $options);

                    if ($result['success']) {
                        $results['imported']++;
                        $results['imported_rules'][] = $result['rule'];
                    } else {
                        $results['skipped']++;
                        $results['errors'][] = "Rule {$index}: " . implode(', ', $result['errors']);
                    }

                } catch (\Exception $e) {
                    $results['skipped']++;
                    $results['errors'][] = "Rule {$index}: {$e->getMessage()}";

                    Log::error('Failed to import rule', [
                        'rule_index' => $index,
                        'rule_name' => $ruleData['name'] ?? 'Unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            Log::info('Rules import completed', [
                'imported' => $results['imported'],
                'skipped' => $results['skipped'],
                'total_errors' => count($results['errors']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $results['success'] = false;
            $results['errors'][] = "Import failed: {$e->getMessage()}";

            Log::error('Rules import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $results;
    }

    /**
     * Import rules from JSON string
     */
    public function importRulesFromJson(string $json, array $options = []): array
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return $this->importRules($data, $options);
    }

    /**
     * Serialize a rule for export
     */
    private function serializeRule(EventRule $rule, array $options = []): array
    {
        $includeIds = $options['include_ids'] ?? false;
        $includeTimestamps = $options['include_timestamps'] ?? false;

        $ruleData = [
            'name' => $rule->name,
            'description' => $rule->description,
            'trigger_type' => $rule->trigger_type,
            'trigger_config' => $rule->trigger_config,
            'is_active' => $rule->is_active,
            'conditions' => [],
            'actions' => [],
        ];

        if ($includeIds) {
            $ruleData['id'] = $rule->id;
        }

        if ($includeTimestamps) {
            $ruleData['created_at'] = $rule->created_at?->toISOString();
            $ruleData['updated_at'] = $rule->updated_at?->toISOString();
        }

        // Serialize conditions
        foreach ($rule->conditions as $condition) {
            $conditionData = [
                'field_path' => $condition->field_path,
                'operator' => $condition->operator,
                'value' => $condition->value,
                'logical_operator' => $condition->logical_operator,
                'priority' => $condition->priority,
                'sort_order' => $condition->sort_order,
            ];

            if ($includeIds) {
                $conditionData['id'] = $condition->id;
            }

            $ruleData['conditions'][] = $conditionData;
        }

        // Serialize actions
        foreach ($rule->actions as $action) {
            $actionData = [
                'action_type' => $action->action_type,
                'action_config' => $action->action_config,
                'is_active' => $action->is_active,
                'priority' => $action->priority,
                'sort_order' => $action->sort_order,
            ];

            if ($includeIds) {
                $actionData['id'] = $action->id;
            }

            $ruleData['actions'][] = $actionData;
        }

        return $ruleData;
    }

    /**
     * Import a single rule
     */
    private function importSingleRule(array $ruleData, array $options = []): array
    {
        $result = [
            'success' => false,
            'rule' => null,
            'errors' => [],
        ];

        // Validate rule data
        $validation = $this->validateRuleData($ruleData);
        if (!$validation['valid']) {
            $result['errors'] = $validation['errors'];
            return $result;
        }

        $skipExisting = $options['skip_existing'] ?? true;
        $updateExisting = $options['update_existing'] ?? false;

        // Check if rule already exists
        $existingRule = EventRule::where('name', $ruleData['name'])->first();

        if ($existingRule) {
            if ($skipExisting && !$updateExisting) {
                $result['errors'][] = 'Rule with this name already exists';
                return $result;
            }

            if ($updateExisting) {
                $rule = $this->updateExistingRule($existingRule, $ruleData);
            } else {
                $rule = $this->createNewRule($ruleData);
                $rule->name = $rule->name . ' (Imported)';
                $rule->save();
            }
        } else {
            $rule = $this->createNewRule($ruleData);
        }

        $result['success'] = true;
        $result['rule'] = $rule;

        return $result;
    }

    /**
     * Create a new rule from imported data
     */
    private function createNewRule(array $ruleData): EventRule
    {
        $rule = EventRule::create([
            'name' => $ruleData['name'],
            'description' => $ruleData['description'],
            'trigger_type' => $ruleData['trigger_type'],
            'trigger_config' => $ruleData['trigger_config'],
            'is_active' => $ruleData['is_active'] ?? true,
        ]);

        // Create conditions
        foreach ($ruleData['conditions'] as $conditionData) {
            EventRuleCondition::create([
                'event_rule_id' => $rule->id,
                'field_path' => $conditionData['field_path'],
                'operator' => $conditionData['operator'],
                'value' => $conditionData['value'],
                'logical_operator' => $conditionData['logical_operator'] ?? 'AND',
                'priority' => $conditionData['priority'] ?? 0,
                'sort_order' => $conditionData['sort_order'] ?? 0,
            ]);
        }

        // Create actions
        foreach ($ruleData['actions'] as $actionData) {
            EventRuleAction::create([
                'event_rule_id' => $rule->id,
                'action_type' => $actionData['action_type'],
                'action_config' => $actionData['action_config'],
                'is_active' => $actionData['is_active'] ?? true,
                'priority' => $actionData['priority'] ?? 0,
                'sort_order' => $actionData['sort_order'] ?? 0,
            ]);
        }

        return $rule->fresh(['conditions', 'actions']);
    }

    /**
     * Update an existing rule with imported data
     */
    private function updateExistingRule(EventRule $existingRule, array $ruleData): EventRule
    {
        $existingRule->update([
            'description' => $ruleData['description'],
            'trigger_type' => $ruleData['trigger_type'],
            'trigger_config' => $ruleData['trigger_config'],
            'is_active' => $ruleData['is_active'] ?? true,
        ]);

        // Remove existing conditions and actions
        $existingRule->conditions()->delete();
        $existingRule->actions()->delete();

        // Create new conditions
        foreach ($ruleData['conditions'] as $conditionData) {
            EventRuleCondition::create([
                'event_rule_id' => $existingRule->id,
                'field_path' => $conditionData['field_path'],
                'operator' => $conditionData['operator'],
                'value' => $conditionData['value'],
                'logical_operator' => $conditionData['logical_operator'] ?? 'AND',
                'priority' => $conditionData['priority'] ?? 0,
                'sort_order' => $conditionData['sort_order'] ?? 0,
            ]);
        }

        // Create new actions
        foreach ($ruleData['actions'] as $actionData) {
            EventRuleAction::create([
                'event_rule_id' => $existingRule->id,
                'action_type' => $actionData['action_type'],
                'action_config' => $actionData['action_config'],
                'is_active' => $actionData['is_active'] ?? true,
                'priority' => $actionData['priority'] ?? 0,
                'sort_order' => $actionData['sort_order'] ?? 0,
            ]);
        }

        return $existingRule->fresh(['conditions', 'actions']);
    }

    /**
     * Validate import data structure
     */
    private function validateImportData(array $data): void
    {
        $validator = Validator::make($data, [
            'version' => 'required|string',
            'rules' => 'required|array|min:1',
            'rules.*.name' => 'required|string|max:255',
            'rules.*.trigger_type' => 'required|string|in:eloquent,sql,schedule,custom',
            'rules.*.conditions' => 'array',
            'rules.*.actions' => 'array',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid import data: ' . implode(', ', $validator->errors()->all()));
        }
    }

    /**
     * Validate individual rule data
     */
    private function validateRuleData(array $ruleData): array
    {
        $validator = Validator::make($ruleData, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|string|in:eloquent,sql,schedule,custom',
            'trigger_config' => 'nullable|array',
            'conditions' => 'array',
            'conditions.*.field_path' => 'required|string',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'nullable',
            'actions' => 'array',
            'actions.*.action_type' => 'required|string',
            'actions.*.action_config' => 'required|array',
        ]);

        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()->all(),
        ];
    }

    /**
     * Create a template for rule export/import
     */
    public function createRuleTemplate(): array
    {
        return [
            'version' => '2.0.0',
            'rules' => [
                [
                    'name' => 'Example Rule',
                    'description' => 'An example rule template',
                    'trigger_type' => 'eloquent',
                    'trigger_config' => [
                        'model' => 'App\\Models\\User',
                        'events' => ['created'],
                    ],
                    'is_active' => true,
                    'conditions' => [
                        [
                            'field_path' => 'email',
                            'operator' => 'contains',
                            'value' => '@example.com',
                            'logical_operator' => 'AND',
                            'priority' => 0,
                            'sort_order' => 0,
                        ],
                    ],
                    'actions' => [
                        [
                            'action_type' => 'email',
                            'action_config' => [
                                'to' => 'admin@example.com',
                                'subject' => 'New User Registration',
                                'message' => 'A new user has registered: {{model.name}} ({{model.email}})',
                            ],
                            'is_active' => true,
                            'priority' => 0,
                            'sort_order' => 0,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get export statistics
     */
    public function getExportStatistics(): array
    {
        return [
            'total_rules' => EventRule::count(),
            'active_rules' => EventRule::where('is_active', true)->count(),
            'rules_by_trigger_type' => EventRule::selectRaw('trigger_type, count(*) as count')
                ->groupBy('trigger_type')
                ->pluck('count', 'trigger_type')
                ->toArray(),
            'total_conditions' => EventRuleCondition::count(),
            'total_actions' => EventRuleAction::count(),
        ];
    }
}