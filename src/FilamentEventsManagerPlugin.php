<?php

namespace St693ava\FilamentEventsManager;

use Filament\Contracts\Plugin;
use Filament\Panel;
use St693ava\FilamentEventsManager\Filament\Pages\EventsDashboard;
use St693ava\FilamentEventsManager\Filament\Pages\RuleTester;
use St693ava\FilamentEventsManager\Filament\Resources\EventRuleResource;
use St693ava\FilamentEventsManager\Filament\Resources\EventLogResource;
use St693ava\FilamentEventsManager\Filament\Widgets\EventsOverviewWidget;
use St693ava\FilamentEventsManager\Filament\Widgets\RecentTriggersWidget;
use St693ava\FilamentEventsManager\Filament\Widgets\ActiveRulesWidget;
use St693ava\FilamentEventsManager\Filament\Widgets\PerformanceWidget;

class FilamentEventsManagerPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-events-manager';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                EventRuleResource::class,
                EventLogResource::class,
            ])
            ->pages([
                EventsDashboard::class,
                RuleTester::class,
            ])
            ->widgets([
                EventsOverviewWidget::class,
                RecentTriggersWidget::class,
                ActiveRulesWidget::class,
                PerformanceWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}