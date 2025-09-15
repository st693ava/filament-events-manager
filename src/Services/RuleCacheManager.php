<?php

namespace St693ava\FilamentEventsManager\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use St693ava\FilamentEventsManager\Models\EventRule;

class RuleCacheManager
{
    private const CACHE_PREFIX = 'filament_events_manager:';
    private const CACHE_TTL = 3600; // 1 hour
    private const ACTIVE_RULES_KEY = 'active_rules';
    private const RULES_BY_TRIGGER_KEY = 'rules_by_trigger';
    private const RULE_CONDITIONS_KEY = 'rule_conditions';
    private const RULE_ACTIONS_KEY = 'rule_actions';

    /**
     * Get all active rules with caching
     */
    public function getActiveRules(): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = $this->getCacheKey(self::ACTIVE_RULES_KEY);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            Log::debug('Loading active rules from database (cache miss)');

            return EventRule::with(['conditions', 'actions'])
                ->where('is_active', true)
                ->orderBy('created_at')
                ->get();
        });
    }

    /**
     * Get rules by trigger type with caching
     */
    public function getRulesByTriggerType(string $triggerType): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = $this->getCacheKey(self::RULES_BY_TRIGGER_KEY . ":{$triggerType}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($triggerType) {
            Log::debug("Loading {$triggerType} rules from database (cache miss)");

            return EventRule::with(['conditions', 'actions'])
                ->where('is_active', true)
                ->where('trigger_type', $triggerType)
                ->orderBy('created_at')
                ->get();
        });
    }

    /**
     * Get rule conditions with caching
     */
    public function getRuleConditions(int $ruleId): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = $this->getCacheKey(self::RULE_CONDITIONS_KEY . ":{$ruleId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($ruleId) {
            return \St693ava\FilamentEventsManager\Models\EventRuleCondition::where('event_rule_id', $ruleId)
                ->orderBy('priority', 'desc')
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Get rule actions with caching
     */
    public function getRuleActions(int $ruleId): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = $this->getCacheKey(self::RULE_ACTIONS_KEY . ":{$ruleId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($ruleId) {
            return \St693ava\FilamentEventsManager\Models\EventRuleAction::where('event_rule_id', $ruleId)
                ->where('is_active', true)
                ->orderBy('priority', 'desc')
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Clear all event manager cache
     */
    public function clearAllCache(): void
    {
        $keys = [
            self::ACTIVE_RULES_KEY,
            self::RULES_BY_TRIGGER_KEY,
            self::RULE_CONDITIONS_KEY,
            self::RULE_ACTIONS_KEY,
        ];

        foreach ($keys as $key) {
            $this->clearCachePattern($key);
        }

        Log::info('Event manager cache cleared');
    }

    /**
     * Clear cache for specific rule
     */
    public function clearRuleCache(int $ruleId): void
    {
        $keys = [
            self::ACTIVE_RULES_KEY,
            self::RULES_BY_TRIGGER_KEY,
            self::RULE_CONDITIONS_KEY . ":{$ruleId}",
            self::RULE_ACTIONS_KEY . ":{$ruleId}",
        ];

        foreach ($keys as $key) {
            $this->clearCachePattern($key);
        }

        Log::info("Cache cleared for rule {$ruleId}");
    }

    /**
     * Clear cache for specific trigger type
     */
    public function clearTriggerTypeCache(string $triggerType): void
    {
        $this->clearCachePattern(self::ACTIVE_RULES_KEY);
        $this->clearCachePattern(self::RULES_BY_TRIGGER_KEY . ":{$triggerType}");

        Log::info("Cache cleared for trigger type {$triggerType}");
    }

    /**
     * Warm up cache with frequently accessed data
     */
    public function warmUpCache(): void
    {
        Log::info('Warming up event manager cache...');

        try {
            // Warm up active rules
            $this->getActiveRules();

            // Warm up rules by trigger type
            $triggerTypes = ['eloquent', 'sql', 'schedule', 'custom'];
            foreach ($triggerTypes as $triggerType) {
                $this->getRulesByTriggerType($triggerType);
            }

            Log::info('Cache warm-up completed');

        } catch (\Exception $e) {
            Log::error('Cache warm-up failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(): array
    {
        $stats = [
            'total_keys' => 0,
            'keys_by_type' => [],
            'estimated_memory' => 0,
        ];

        try {
            // Get cache store
            $store = Cache::store();

            // If using Redis or array cache, we can get more detailed stats
            if (method_exists($store, 'getRedis')) {
                $redis = $store->getRedis();
                $keys = $redis->keys($this->getCacheKey('*'));
                $stats['total_keys'] = count($keys);

                foreach ($keys as $key) {
                    $keyWithoutPrefix = str_replace($this->getCacheKey(''), '', $key);
                    $type = explode(':', $keyWithoutPrefix)[0];
                    $stats['keys_by_type'][$type] = ($stats['keys_by_type'][$type] ?? 0) + 1;

                    // Estimate memory usage (very rough)
                    $stats['estimated_memory'] += strlen($redis->get($key)) ?? 0;
                }
            }

        } catch (\Exception $e) {
            Log::warning('Failed to get cache statistics', [
                'error' => $e->getMessage(),
            ]);
        }

        return $stats;
    }

    /**
     * Check if caching is enabled and working
     */
    public function isCacheWorking(): bool
    {
        try {
            $testKey = $this->getCacheKey('health_check');
            $testValue = 'test_' . time();

            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            return $retrieved === $testValue;

        } catch (\Exception $e) {
            Log::error('Cache health check failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get cache key with prefix
     */
    private function getCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . $key;
    }

    /**
     * Clear cache entries matching a pattern
     */
    private function clearCachePattern(string $pattern): void
    {
        try {
            $store = Cache::store();

            // For Redis cache
            if (method_exists($store, 'getRedis')) {
                $redis = $store->getRedis();
                $keys = $redis->keys($this->getCacheKey($pattern . '*'));

                foreach ($keys as $key) {
                    $redis->del($key);
                }
                return;
            }

            // For other cache stores, we need to track keys manually
            // This is a limitation of some cache drivers
            Cache::forget($this->getCacheKey($pattern));

        } catch (\Exception $e) {
            Log::warning('Failed to clear cache pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Schedule cache refresh for a rule
     */
    public function scheduleRefresh(int $ruleId, int $delayMinutes = 5): void
    {
        // In a real implementation, this would use Laravel's job queue
        // For now, we'll just clear the cache immediately
        $this->clearRuleCache($ruleId);

        Log::info("Scheduled cache refresh for rule {$ruleId}");
    }

    /**
     * Get cache configuration information
     */
    public function getCacheConfig(): array
    {
        return [
            'default_driver' => config('cache.default'),
            'prefix' => self::CACHE_PREFIX,
            'ttl' => self::CACHE_TTL,
            'stores' => array_keys(config('cache.stores', [])),
        ];
    }
}