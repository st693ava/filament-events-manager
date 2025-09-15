<?php

use St693ava\FilamentEventsManager\Filament\Resources\EventLogResource;
use St693ava\FilamentEventsManager\Filament\Resources\EventLogResource\Pages\ListEventLogs;
use St693ava\FilamentEventsManager\Filament\Resources\EventLogResource\Pages\ViewEventLog;
use St693ava\FilamentEventsManager\Models\EventLog;

it('can create the resource', function () {
    $resource = new EventLogResource();

    expect($resource)->toBeInstanceOf(EventLogResource::class);
});

it('has correct model', function () {
    expect(EventLogResource::getModel())->toBe(EventLog::class);
});

it('has correct navigation properties', function () {
    expect(EventLogResource::getNavigationLabel())->toBe('Logs de Eventos');
    expect(EventLogResource::getModelLabel())->toBe('Log de Evento');
    expect(EventLogResource::getPluralModelLabel())->toBe('Logs de Eventos');
});

it('has correct navigation group', function () {
    expect(EventLogResource::getNavigationGroup())->toBe('Gestão de Eventos');
});

it('can get navigation badge', function () {
    // Criar logs de ontem e hoje
    \Workbench\Database\Factories\EventLogFactory::new(['triggered_at' => now()->subDays(2)])->create();
    \Workbench\Database\Factories\EventLogFactory::new(['triggered_at' => now()->subHours(5)])->create();
    \Workbench\Database\Factories\EventLogFactory::new(['triggered_at' => now()->subMinutes(30)])->create();

    expect(EventLogResource::getNavigationBadge())->toBe('2');
});

it('cannot create logs manually', function () {
    expect(EventLogResource::canCreate())->toBeFalse();
});

it('cannot edit logs', function () {
    $log = createEventLog();

    expect(EventLogResource::canEdit($log))->toBeFalse();
});

it('cannot delete logs', function () {
    $log = createEventLog();

    expect(EventLogResource::canDelete($log))->toBeFalse();
});

it('has correct pages configuration', function () {
    $pages = EventLogResource::getPages();

    expect($pages)->toHaveKey('index');
    expect($pages)->toHaveKey('view');
    expect($pages)->not()->toHaveKey('create');
    expect($pages)->not()->toHaveKey('edit');

    // Verify page registrations contain correct types
    expect($pages['index'])->toBeInstanceOf(\Filament\Resources\Pages\PageRegistration::class);
    expect($pages['view'])->toBeInstanceOf(\Filament\Resources\Pages\PageRegistration::class);
});

// All other tests require full Filament setup with Livewire, skipped for unit testing
it('can render pages and perform operations', function () {
    expect(true)->toBeTrue(); // Integration tests needed for full UI testing
})->skip('Requires full Filament + Livewire setup for integration testing');