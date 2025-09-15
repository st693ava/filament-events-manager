<?php

namespace St693ava\FilamentEventsManager;

use Illuminate\Support\ServiceProvider;
use St693ava\FilamentEventsManager\Actions\ActionManager;
use St693ava\FilamentEventsManager\Actions\Executors\EmailAction;
use St693ava\FilamentEventsManager\Actions\Executors\ActivityLogAction;
use St693ava\FilamentEventsManager\Actions\Executors\WebhookActionExecutor;
use St693ava\FilamentEventsManager\Actions\Executors\NotificationActionExecutor;
use St693ava\FilamentEventsManager\Console\Commands\TestEventRuleCommand;
use St693ava\FilamentEventsManager\Console\Commands\ProcessScheduledRulesCommand;
use St693ava\FilamentEventsManager\Console\Commands\ExportRulesCommand;
use St693ava\FilamentEventsManager\Console\Commands\ImportRulesCommand;
use St693ava\FilamentEventsManager\Listeners\EloquentEventListener;
use St693ava\FilamentEventsManager\Listeners\QueryEventListener;
use St693ava\FilamentEventsManager\Services\ConditionEvaluator;
use St693ava\FilamentEventsManager\Services\ContextCollector;
use St693ava\FilamentEventsManager\Services\RuleEngine;
use St693ava\FilamentEventsManager\Services\OptimizedRuleEngine;
use St693ava\FilamentEventsManager\Services\TemplateRenderer;
use St693ava\FilamentEventsManager\Services\SqlParser;
use St693ava\FilamentEventsManager\Services\ScheduleTriggerManager;
use St693ava\FilamentEventsManager\Services\CustomEventManager;
use St693ava\FilamentEventsManager\Services\RuleImportExportManager;
use St693ava\FilamentEventsManager\Services\RuleCacheManager;

class EventsManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/filament-events-manager.php',
            'filament-events-manager'
        );

        // Registar serviços principais
        $this->app->singleton(TemplateRenderer::class);
        $this->app->singleton(ContextCollector::class);
        $this->app->singleton(ConditionEvaluator::class);
        $this->app->singleton(RuleEngine::class);

        // Release 2.0.0 services
        $this->app->singleton(SqlParser::class);
        $this->app->singleton(RuleCacheManager::class);
        $this->app->singleton(OptimizedRuleEngine::class);
        $this->app->singleton(ScheduleTriggerManager::class);
        $this->app->singleton(CustomEventManager::class);
        $this->app->singleton(RuleImportExportManager::class);
        $this->app->singleton(QueryEventListener::class);

        // Registar gestor de ações
        $this->app->singleton(ActionManager::class, function ($app) {
            $manager = new ActionManager();

            // Registar ações padrão
            $manager->register('email', EmailAction::class);
            $manager->register('activity_log', ActivityLogAction::class);

            // Registar novas ações do Release 1.2.0
            $manager->register('webhook', WebhookActionExecutor::class);
            $manager->register('notification', NotificationActionExecutor::class);

            return $manager;
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/filament-events-manager.php' => config_path('filament-events-manager.php'),
        ], 'filament-events-manager-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'filament-events-manager-migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-events-manager');

        // Registar listeners de eventos
        if ($this->app->runningInConsole() === false) {
            // Eloquent events
            $this->app->make(EloquentEventListener::class)->register();

            // SQL query events
            if (config('filament-events-manager.sql_events.enabled', false)) {
                $this->registerQueryEventListener();
            }

            // Custom events
            $this->app->make(CustomEventManager::class)->registerCustomEventListeners();
        }

        // Comandos Artisan
        if ($this->app->runningInConsole()) {
            $this->commands([
                TestEventRuleCommand::class,
                ProcessScheduledRulesCommand::class,
                ExportRulesCommand::class,
                ImportRulesCommand::class,
            ]);
        }
    }

    /**
     * Register SQL query event listener
     */
    private function registerQueryEventListener(): void
    {
        $queryListener = $this->app->make(QueryEventListener::class);

        // Register with Laravel's DB events
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Database\Events\QueryExecuted::class,
            [$queryListener, 'handle']
        );
    }

    public function provides(): array
    {
        return [
            ActionManager::class,
            TemplateRenderer::class,
            ContextCollector::class,
            ConditionEvaluator::class,
            RuleEngine::class,
            OptimizedRuleEngine::class,
            SqlParser::class,
            RuleCacheManager::class,
            ScheduleTriggerManager::class,
            CustomEventManager::class,
            RuleImportExportManager::class,
            QueryEventListener::class,
        ];
    }
}