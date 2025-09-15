<?php

namespace Tests\Browser;

use Orchestra\Testbench\Dusk\TestCase as BaseTestCase;
use Laravel\Dusk\Browser;
use St693ava\FilamentEventsManager\EventsManagerServiceProvider;
use Filament\FilamentServiceProvider;
use Livewire\LivewireServiceProvider;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Factories\Factory;

abstract class DuskTestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Workbench\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Configurar Dusk
        Browser::macro('waitForFilamentToLoad', function () {
            return $this->waitUntilMissing('.fi-app-loading');
        });

        Browser::macro('loginAsFilamentUser', function ($user = null) {
            if (!$user) {
                $user = \Workbench\App\Models\User::factory()->create();
            }

            $this->visit('/admin/login')
                ->type('email', $user->email)
                ->type('password', 'password')
                ->press('Sign in')
                ->waitForFilamentToLoad();

            return $this;
        });
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            ActivitylogServiceProvider::class,
            EventsManagerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        // Configuração de base de dados
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configuração de autenticação
        config()->set('auth.providers.users.model', \Workbench\App\Models\User::class);

        // Configuração do Filament
        config()->set('filament.default_panel', 'admin');

        $this->setUpDatabase($app);
        $this->setUpFilament();
    }

    protected function setUpDatabase($app): void
    {
        // Criar tabela users
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
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function setUpFilament(): void
    {
        $panel = \Filament\Panel::make()
            ->id('admin')
            ->path('/admin')
            ->default()
            ->login()
            ->resources([
                \St693ava\FilamentEventsManager\Filament\Resources\EventRuleResource::class,
                \St693ava\FilamentEventsManager\Filament\Resources\EventLogResource::class,
            ]);

        \Filament\Facades\Filament::registerPanel($panel);
    }

    protected function getApplicationTimezone($app): string
    {
        return 'Europe/Lisbon';
    }
}