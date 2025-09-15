<?php

namespace St693ava\FilamentEventsManager\Listeners;

use Illuminate\Support\Facades\Cache;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Services\RuleEngine;

class GlobalEventInterceptor
{
    public function __construct(
        private RuleEngine $ruleEngine
    ) {}

    public function handle(string $eventName, array $data): void
    {
        \Illuminate\Support\Facades\Log::info('GlobalEventInterceptor: Processing event', [
            'event_name' => $eventName,
            'model_class' => isset($data[0]) ? get_class($data[0]) : null,
            'model_id' => $data[0]->id ?? null,
        ]);

        // Verificar se temos regras ativas para este tipo de evento
        if (! $this->hasActiveRules($eventName)) {
            \Illuminate\Support\Facades\Log::info('GlobalEventInterceptor: No active rules found', [
                'event_name' => $eventName,
            ]);

            return;
        }

        \Illuminate\Support\Facades\Log::info('GlobalEventInterceptor: Active rules found, processing', [
            'event_name' => $eventName,
        ]);

        // Processar o evento através do motor de regras
        $this->ruleEngine->processEvent($eventName, $data);
    }

    private function hasActiveRules(string $eventName): bool
    {
        $eventType = $this->extractEventType($eventName);
        $cacheKey = "has_active_rules_{$eventType}";

        \Illuminate\Support\Facades\Log::info('GlobalEventInterceptor: Checking for active rules', [
            'original_event' => $eventName,
            'extracted_event_type' => $eventType,
            'cache_key' => $cacheKey,
        ]);

        $result = Cache::remember($cacheKey, 300, function () use ($eventType, $eventName) {
            $count = EventRule::active()
                ->where('trigger_type', 'eloquent')
                ->whereJsonContains('trigger_config->events', $eventType)
                ->count();

            \Illuminate\Support\Facades\Log::info('GlobalEventInterceptor: Database query result', [
                'event_type' => $eventType,
                'rules_found' => $count,
                'original_event' => $eventName,
            ]);

            return $count > 0;
        });

        return $result;
    }

    private function extractEventType(string $eventName): string
    {
        // Converter "eloquent.created: App\Models\User" para "created"
        if (str_starts_with($eventName, 'eloquent.')) {
            $parts = explode(':', $eventName);
            $eventPart = str_replace('eloquent.', '', $parts[0]);

            \Illuminate\Support\Facades\Log::info('GlobalEventInterceptor: Event type extraction', [
                'original_event' => $eventName,
                'parts' => $parts,
                'extracted_type' => $eventPart,
            ]);

            return $eventPart;
        }

        return $eventName;
    }
}
