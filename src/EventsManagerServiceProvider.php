<?php

namespace St693ava\FilamentEventsManager;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\ComponentAttributeBag;
use St693ava\FilamentEventsManager\Actions\ActionManager;
use St693ava\FilamentEventsManager\Actions\Executors\ActivityLogAction;
use St693ava\FilamentEventsManager\Actions\Executors\EmailAction;
use St693ava\FilamentEventsManager\Actions\Executors\NotificationActionExecutor;
use St693ava\FilamentEventsManager\Actions\Executors\WebhookActionExecutor;
use St693ava\FilamentEventsManager\Console\Commands\ExportRulesCommand;
use St693ava\FilamentEventsManager\Console\Commands\ImportRulesCommand;
use St693ava\FilamentEventsManager\Console\Commands\InstallDefaultRulesCommand;
use St693ava\FilamentEventsManager\Console\Commands\ProcessScheduledRulesCommand;
use St693ava\FilamentEventsManager\Console\Commands\TestEventRuleCommand;
use St693ava\FilamentEventsManager\Listeners\EloquentEventListener;
use St693ava\FilamentEventsManager\Listeners\QueryEventListener;
use St693ava\FilamentEventsManager\Services\ConditionEvaluator;
use St693ava\FilamentEventsManager\Services\ContextCollector;
use St693ava\FilamentEventsManager\Services\CustomEventManager;
use St693ava\FilamentEventsManager\Services\OptimizedRuleEngine;
use St693ava\FilamentEventsManager\Services\RuleCacheManager;
use St693ava\FilamentEventsManager\Services\RuleEngine;
use St693ava\FilamentEventsManager\Services\RuleImportExportManager;
use St693ava\FilamentEventsManager\Services\ScheduleTriggerManager;
use St693ava\FilamentEventsManager\Services\SqlParser;
use St693ava\FilamentEventsManager\Services\TemplateRenderer;

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
            $manager = new ActionManager;

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

        // Filament v4 compatibility fixes
        // Since this is a compatibility issue with the testing environment, disable for screenshots
        // We'll create a minimal test that doesn't use the full Filament stack

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-events-manager');

        // Load test routes for screenshots in testing environment
        if ($this->app->environment('testing')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/test-web.php');
        }

        // Registar listeners de eventos
        if ($this->shouldRegisterEventListeners()) {
            // Debug log to verify this is being executed
            \Illuminate\Support\Facades\Log::info('EventsManagerServiceProvider: Registering event listeners');

            // Eloquent events
            $this->app->make(EloquentEventListener::class)->register();
            \Illuminate\Support\Facades\Log::info('EventsManagerServiceProvider: EloquentEventListener registered');

            // SQL query events
            if (config('filament-events-manager.sql_events.enabled', false)) {
                $this->registerQueryEventListener();
            }

            // Custom events
            $this->app->make(CustomEventManager::class)->registerCustomEventListeners();
        } else {
            \Illuminate\Support\Facades\Log::info('EventsManagerServiceProvider: Event listeners NOT registered', [
                'running_in_console' => $this->app->runningInConsole(),
                'running_unit_tests' => $this->app->runningUnitTests(),
            ]);
        }

        // Comandos Artisan
        if ($this->app->runningInConsole()) {
            $this->commands([
                TestEventRuleCommand::class,
                ProcessScheduledRulesCommand::class,
                ExportRulesCommand::class,
                ImportRulesCommand::class,
                InstallDefaultRulesCommand::class,
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

    /**
     * Determine if event listeners should be registered
     */
    private function shouldRegisterEventListeners(): bool
    {
        // Always register for web requests
        if (! $this->app->runningInConsole()) {
            return true;
        }

        // Register for unit tests
        if ($this->app->runningUnitTests()) {
            return true;
        }

        // Always register for console (including tinker) - temporary fix for debugging
        // TODO: Make this configurable via config once issue is resolved
        return true;

        // Allow config to force registration in console (useful for tinker, testing)
        if (config('filament-events-manager.register_console_events', false)) {
            return true;
        }

        return false;
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
