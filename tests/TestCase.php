<?php

namespace St693ava\FilamentEventsManager\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use St693ava\FilamentEventsManager\EventsManagerServiceProvider;
use Spatie\Activitylog\ActivitylogServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'St693ava\\FilamentEventsManager\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            EventsManagerServiceProvider::class,
            ActivitylogServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Executar migrations
        $migration = include __DIR__.'/../database/migrations/2024_01_01_000001_create_event_rules_table.php';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/2024_01_01_000002_create_event_rule_conditions_table.php';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/2024_01_01_000003_create_event_rule_actions_table.php';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/2024_01_01_000004_create_event_logs_table.php';
        $migration->up();

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
    }
}