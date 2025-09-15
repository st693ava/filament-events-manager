<?php

namespace Tests;

use Filament\FilamentServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use St693ava\FilamentEventsManager\EventsManagerServiceProvider;
use Spatie\Activitylog\ActivitylogServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Workbench\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
            FilamentServiceProvider::class,
            ActivitylogServiceProvider::class,
            EventsManagerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        // Configuração de base de dados para testes
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configuração de cache para testes
        config()->set('cache.default', 'array');

        // Configuração de filas para testes
        config()->set('queue.default', 'sync');

        // Configuração de mail para testes
        config()->set('mail.default', 'array');

        // Configuração de session para testes (necessário para ViewErrorBag)
        config()->set('session.driver', 'array');

        // Configuração do Events Manager
        config()->set('filament-events-manager.cache.enabled', false);
        config()->set('filament-events-manager.async_processing', false);
        config()->set('filament-events-manager.performance.enable_query_cache', false);

        // Configuração de autenticação
        config()->set('auth.providers.users.model', \Workbench\App\Models\User::class);
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web.driver', 'session');
        config()->set('auth.guards.web.provider', 'users');

        $this->setUpDatabase($app);
        $this->setUpFilament();
    }

    protected function setUpDatabase($app): void
    {
        // Criar tabela users para testes
        $app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Criar tabela activity_log para Spatie
        $app['db']->connection()->getSchemaBuilder()->create('activity_log', function ($table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });

        // Criar tabelas de exemplo para testes
        $app['db']->connection()->getSchemaBuilder()->create('test_products', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('stock_quantity')->default(0);
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('test_orders', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('total', 10, 2);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    protected function defineDatabaseMigrations(): void
    {
        // Usar loadMigrationsFrom para carregar as migrações do package
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function setUpFilament(): void
    {
        $panel = \Filament\Panel::make()
            ->id('admin')
            ->path('/admin')
            ->default()
            ->login()
            ->resources([
                \St693ava\FilamentEventsManager\Filament\Resources\SimpleEventRuleResource::class,
            ]);

        \Filament\Facades\Filament::registerPanel($panel);
    }

}