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
        // Registar listeners para todos os eventos Eloquent principais
        $events = [
            'eloquent.retrieved',
            'eloquent.creating',
            'eloquent.created',
            'eloquent.updating',
            'eloquent.updated',
            'eloquent.saving',
            'eloquent.saved',
            'eloquent.deleting',
            'eloquent.deleted',
            'eloquent.restoring',
            'eloquent.restored',
            'eloquent.replicating',
        ];

        foreach ($events as $event) {
            Event::listen($event, function (string $eventName, array $data) {
                $this->globalEventInterceptor->handle($eventName, $data);
            });
        }
    }
}