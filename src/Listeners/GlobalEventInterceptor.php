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
        // Verificar se temos regras ativas para este tipo de evento
        if (!$this->hasActiveRules($eventName)) {
            return;
        }

        // Processar o evento através do motor de regras
        $this->ruleEngine->processEvent($eventName, $data);
    }

    private function hasActiveRules(string $eventName): bool
    {
        $eventType = $this->extractEventType($eventName);
        $cacheKey = "has_active_rules_{$eventType}";

        return Cache::remember($cacheKey, 300, function () use ($eventType) {
            return EventRule::active()
                ->where('trigger_type', 'eloquent')
                ->whereJsonContains('trigger_config->events', $eventType)
                ->exists();
        });
    }

    private function extractEventType(string $eventName): string
    {
        // Converter "eloquent.created: App\Models\User" para "created"
        if (str_starts_with($eventName, 'eloquent.')) {
            $parts = explode(':', $eventName);
            $eventPart = str_replace('eloquent.', '', $parts[0]);
            return $eventPart;
        }

        return $eventName;
    }
}