<?php

namespace St693ava\FilamentEventsManager\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Services\SqlParser;
use St693ava\FilamentEventsManager\Services\RuleEngine;
use St693ava\FilamentEventsManager\Support\EventContext;

class QueryEventListener
{
    private SqlParser $sqlParser;
    private RuleEngine $ruleEngine;

    public function __construct(SqlParser $sqlParser, RuleEngine $ruleEngine)
    {
        $this->sqlParser = $sqlParser;
        $this->ruleEngine = $ruleEngine;
    }

    public function handle(QueryExecuted $event): void
    {
        try {
            // Parse the SQL query to understand its type and tables
            $queryInfo = $this->sqlParser->parse($event->sql, $event->bindings);

            // Only process queries that affect data (INSERT, UPDATE, DELETE)
            if (!$this->shouldProcessQuery($queryInfo)) {
                return;
            }

            // Get rules that are triggered by SQL queries
            $rules = $this->getApplicableRules($queryInfo);

            if ($rules->isEmpty()) {
                return;
            }

            // Create event context
            $context = $this->createQueryEventContext($event, $queryInfo);

            // Process each applicable rule
            foreach ($rules as $rule) {
                $this->ruleEngine->processRule($rule, $queryInfo['data'] ?? [], $context);
            }

        } catch (\Exception $e) {
            Log::error('Query event listener error', [
                'sql' => $event->sql,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine if the query should be processed
     */
    private function shouldProcessQuery(array $queryInfo): bool
    {
        // Only process data-changing operations
        $processableTypes = ['INSERT', 'UPDATE', 'DELETE'];

        return in_array(strtoupper($queryInfo['type']), $processableTypes) &&
               !empty($queryInfo['tables']) &&
               !$this->isSystemQuery($queryInfo);
    }

    /**
     * Check if this is a system query that should be ignored
     */
    private function isSystemQuery(array $queryInfo): bool
    {
        $systemTables = [
            'migrations',
            'failed_jobs',
            'password_reset_tokens',
            'personal_access_tokens',
            'sessions',
            'cache',
            'jobs',
            'telescope_entries',
            'telescope_entries_tags',
            'telescope_monitoring',
        ];

        foreach ($queryInfo['tables'] as $table) {
            if (in_array($table, $systemTables)) {
                return true;
            }
        }

        // Ignore our own event tables to prevent loops
        $eventTables = [
            'event_rules',
            'event_rule_conditions',
            'event_rule_actions',
            'event_logs',
        ];

        foreach ($queryInfo['tables'] as $table) {
            if (in_array($table, $eventTables)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get rules that should be triggered by this query
     */
    private function getApplicableRules(array $queryInfo)
    {
        return EventRule::where('is_active', true)
            ->where('trigger_type', 'sql')
            ->get()
            ->filter(function ($rule) use ($queryInfo) {
                $config = $rule->trigger_config ?? [];

                // Check if query type matches rule configuration
                if (isset($config['operation']) &&
                    strtoupper($config['operation']) !== strtoupper($queryInfo['type'])) {
                    return false;
                }

                // Check if table matches rule configuration
                if (isset($config['table'])) {
                    $configTables = is_array($config['table']) ? $config['table'] : [$config['table']];
                    $hasMatchingTable = !empty(array_intersect($configTables, $queryInfo['tables']));

                    if (!$hasMatchingTable) {
                        return false;
                    }
                }

                return true;
            });
    }

    /**
     * Create event context for SQL queries
     */
    private function createQueryEventContext(QueryExecuted $event, array $queryInfo): EventContext
    {
        $context = new EventContext();

        // Basic SQL information
        $context->set('event_type', 'sql');
        $context->set('sql_query', $event->sql);
        $context->set('sql_bindings', $event->bindings);
        $context->set('query_time', $event->time);
        $context->set('connection_name', $event->connectionName);

        // Parsed query information
        $context->set('query_type', $queryInfo['type']);
        $context->set('affected_tables', $queryInfo['tables']);
        $context->set('estimated_rows', $queryInfo['estimated_rows'] ?? null);

        // Set data if available
        if (isset($queryInfo['data'])) {
            $context->set('query_data', $queryInfo['data']);
        }

        // User context (if available)
        if (auth()->check()) {
            $context->set('user_id', auth()->id());
            $context->set('user_name', auth()->user()->name ?? 'Unknown');
            $context->set('user_email', auth()->user()->email ?? 'unknown@example.com');
        }

        // Request context
        if (request()) {
            $context->set('ip_address', request()->ip());
            $context->set('user_agent', request()->userAgent());
            $context->set('request_url', request()->fullUrl());
            $context->set('request_method', request()->method());
        }

        // Timing
        $context->set('triggered_at', now()->toISOString());

        return $context;
    }
}