<?php

namespace St693ava\FilamentEventsManager\Services;

use Illuminate\Support\Str;

class SqlParser
{
    /**
     * Parse SQL query and extract relevant information
     */
    public function parse(string $sql, array $bindings = []): array
    {
        // Clean and normalize SQL
        $normalizedSql = $this->normalizeSql($sql);

        // Determine query type
        $type = $this->getQueryType($normalizedSql);

        // Extract tables involved
        $tables = $this->extractTables($normalizedSql, $type);

        // Extract data if possible
        $data = $this->extractData($normalizedSql, $bindings, $type);

        // Estimate affected rows (basic estimation)
        $estimatedRows = $this->estimateAffectedRows($normalizedSql, $bindings);

        return [
            'type' => $type,
            'tables' => $tables,
            'data' => $data,
            'estimated_rows' => $estimatedRows,
            'normalized_sql' => $normalizedSql,
            'original_sql' => $sql,
            'bindings' => $bindings,
        ];
    }

    /**
     * Clean and normalize SQL for easier parsing
     */
    private function normalizeSql(string $sql): string
    {
        // Remove extra whitespace and newlines
        $sql = preg_replace('/\s+/', ' ', trim($sql));

        // Convert to uppercase for keywords but preserve identifiers
        $sql = preg_replace_callback('/\b(SELECT|INSERT|UPDATE|DELETE|FROM|INTO|WHERE|SET|VALUES|JOIN|LEFT|RIGHT|INNER|OUTER|ON|AND|OR|ORDER|GROUP|BY|HAVING|LIMIT|OFFSET)\b/i', function ($matches) {
            return strtoupper($matches[1]);
        }, $sql);

        return $sql;
    }

    /**
     * Determine the type of SQL query
     */
    private function getQueryType(string $sql): string
    {
        if (preg_match('/^SELECT\s+/i', $sql)) {
            return 'SELECT';
        }

        if (preg_match('/^INSERT\s+/i', $sql)) {
            return 'INSERT';
        }

        if (preg_match('/^UPDATE\s+/i', $sql)) {
            return 'UPDATE';
        }

        if (preg_match('/^DELETE\s+/i', $sql)) {
            return 'DELETE';
        }

        if (preg_match('/^CREATE\s+/i', $sql)) {
            return 'CREATE';
        }

        if (preg_match('/^DROP\s+/i', $sql)) {
            return 'DROP';
        }

        if (preg_match('/^ALTER\s+/i', $sql)) {
            return 'ALTER';
        }

        return 'UNKNOWN';
    }

    /**
     * Extract table names from SQL query
     */
    private function extractTables(string $sql, string $type): array
    {
        $tables = [];

        switch ($type) {
            case 'SELECT':
                $tables = $this->extractSelectTables($sql);
                break;

            case 'INSERT':
                $tables = $this->extractInsertTables($sql);
                break;

            case 'UPDATE':
                $tables = $this->extractUpdateTables($sql);
                break;

            case 'DELETE':
                $tables = $this->extractDeleteTables($sql);
                break;
        }

        // Clean table names (remove backticks, quotes, schema prefixes)
        return array_map(function ($table) {
            $table = trim($table, '`"\'');
            // Remove schema prefix if present (e.g., database.table -> table)
            if (strpos($table, '.') !== false) {
                $parts = explode('.', $table);
                $table = end($parts);
            }
            return $table;
        }, $tables);
    }

    /**
     * Extract tables from SELECT query
     */
    private function extractSelectTables(string $sql): array
    {
        $tables = [];

        // Main FROM clause
        if (preg_match('/FROM\s+([^WHERE|GROUP|ORDER|LIMIT|HAVING|JOIN]+)/i', $sql, $matches)) {
            $fromClause = trim($matches[1]);
            $tables = array_merge($tables, $this->parseTableList($fromClause));
        }

        // JOIN clauses
        if (preg_match_all('/JOIN\s+([^\s]+)/i', $sql, $matches)) {
            foreach ($matches[1] as $table) {
                $tables[] = $table;
            }
        }

        return array_unique($tables);
    }

    /**
     * Extract tables from INSERT query
     */
    private function extractInsertTables(string $sql): array
    {
        if (preg_match('/INSERT\s+INTO\s+([^\s(]+)/i', $sql, $matches)) {
            return [$matches[1]];
        }

        return [];
    }

    /**
     * Extract tables from UPDATE query
     */
    private function extractUpdateTables(string $sql): array
    {
        if (preg_match('/UPDATE\s+([^\s]+)/i', $sql, $matches)) {
            return [$matches[1]];
        }

        return [];
    }

    /**
     * Extract tables from DELETE query
     */
    private function extractDeleteTables(string $sql): array
    {
        if (preg_match('/DELETE\s+FROM\s+([^\s]+)/i', $sql, $matches)) {
            return [$matches[1]];
        }

        return [];
    }

    /**
     * Parse a comma-separated list of tables (with potential aliases)
     */
    private function parseTableList(string $tableList): array
    {
        $tables = [];
        $parts = explode(',', $tableList);

        foreach ($parts as $part) {
            $part = trim($part);
            // Handle table aliases (e.g., "users u" or "users AS u")
            $tableParts = preg_split('/\s+(AS\s+)?/i', $part);
            $tables[] = trim($tableParts[0]);
        }

        return $tables;
    }

    /**
     * Extract data from SQL query where possible
     */
    private function extractData(string $sql, array $bindings, string $type): ?array
    {
        switch ($type) {
            case 'INSERT':
                return $this->extractInsertData($sql, $bindings);

            case 'UPDATE':
                return $this->extractUpdateData($sql, $bindings);

            default:
                return null;
        }
    }

    /**
     * Extract data from INSERT query
     */
    private function extractInsertData(string $sql, array $bindings): ?array
    {
        // Try to extract column names
        if (preg_match('/INSERT\s+INTO\s+[^\s(]+\s*\(([^)]+)\)/i', $sql, $matches)) {
            $columns = array_map('trim', explode(',', $matches[1]));
            $columns = array_map(function ($col) {
                return trim($col, '`"\'');
            }, $columns);

            // Map bindings to columns if we have the same count
            if (count($columns) === count($bindings)) {
                return array_combine($columns, $bindings);
            }
        }

        // If we can't match columns to bindings, just return bindings with numeric keys
        return array_combine(range(0, count($bindings) - 1), $bindings);
    }

    /**
     * Extract data from UPDATE query
     */
    private function extractUpdateData(string $sql, array $bindings): ?array
    {
        // This is more complex as UPDATE queries can have WHERE conditions
        // For now, return the bindings as-is
        // A more sophisticated implementation would parse SET clauses
        return array_combine(range(0, count($bindings) - 1), $bindings);
    }

    /**
     * Estimate number of rows affected (very basic)
     */
    private function estimateAffectedRows(string $sql, array $bindings): ?int
    {
        // Check for LIMIT clause
        if (preg_match('/LIMIT\s+(\d+)/i', $sql, $matches)) {
            return (int) $matches[1];
        }

        // For INSERTs, count VALUES clauses
        if (preg_match('/INSERT\s+INTO/i', $sql)) {
            if (preg_match_all('/\([^)]+\)/i', $sql, $matches)) {
                return count($matches[0]) - 1; // Subtract 1 for column definition
            }
        }

        // For other queries without LIMIT, we can't easily estimate
        return null;
    }

    /**
     * Check if a query is a data modification query
     */
    public function isDataModification(string $type): bool
    {
        return in_array(strtoupper($type), ['INSERT', 'UPDATE', 'DELETE']);
    }

    /**
     * Check if a query is a read-only query
     */
    public function isReadOnly(string $type): bool
    {
        return in_array(strtoupper($type), ['SELECT']);
    }

    /**
     * Get query complexity score (basic heuristic)
     */
    public function getComplexityScore(string $sql): int
    {
        $score = 0;

        // Count JOINs
        $score += substr_count(strtoupper($sql), 'JOIN') * 2;

        // Count subqueries
        $score += substr_count($sql, '(') * 1;

        // Count WHERE conditions (approximate)
        $score += substr_count(strtoupper($sql), ' AND ') * 1;
        $score += substr_count(strtoupper($sql), ' OR ') * 1;

        // Count aggregate functions
        $score += preg_match_all('/\b(COUNT|SUM|AVG|MIN|MAX|GROUP_CONCAT)\s*\(/i', $sql);

        return $score;
    }
}