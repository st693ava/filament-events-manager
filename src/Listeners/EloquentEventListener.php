<?php

namespace St693ava\FilamentEventsManager\Listeners;

use Illuminate\Support\Facades\Event;

class EloquentEventListener
{
    public function __construct(
        private GlobalEventInterceptor $globalEventInterceptor
    ) {}

    public function register(): void
    {
        \Illuminate\Support\Facades\Log::info('EloquentEventListener: Starting registration for specific Eloquent events');

        // Registar apenas os eventos Eloquent específicos (não usar wildcard geral)
        $eloquentEvents = [
            'eloquent.retrieved*',
            'eloquent.creating*',
            'eloquent.created*',
            'eloquent.updating*',
            'eloquent.updated*',
            'eloquent.saving*',
            'eloquent.saved*',
            'eloquent.deleting*',
            'eloquent.deleted*',
            'eloquent.restoring*',
            'eloquent.restored*',
            'eloquent.replicating*',
        ];

        foreach ($eloquentEvents as $eventPattern) {
            Event::listen($eventPattern, function (string $eventName, array $data) {
                \Illuminate\Support\Facades\Log::info('EloquentEventListener: Eloquent event captured', [
                    'event' => $eventName,
                    'model_class' => isset($data[0]) ? get_class($data[0]) : null,
                    'model_id' => $data[0]->id ?? null,
                ]);
                $this->globalEventInterceptor->handle($eventName, $data);
            });
        }

        \Illuminate\Support\Facades\Log::info('EloquentEventListener: Specific Eloquent event listeners registered', [
            'events_count' => count($eloquentEvents),
        ]);
    }
}
