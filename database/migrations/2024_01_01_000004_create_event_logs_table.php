<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_rule_id')->constrained('event_rules');

            // Informação sobre o trigger
            $table->string('trigger_type', 50);
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('event_name');

            // Contexto completo do evento
            $table->json('context');

            // Resultados das ações executadas
            $table->json('actions_executed');

            // Tempo de execução em milissegundos
            $table->integer('execution_time_ms');

            // Timestamp do trigger
            $table->timestamp('triggered_at');

            // Contexto do utilizador
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Contexto do request
            $table->text('request_url')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('session_id')->nullable();

            // Índices para performance e consultas frequentes
            $table->index(['event_rule_id', 'triggered_at']);
            $table->index(['model_type', 'model_id']);
            $table->index(['user_id', 'triggered_at']);
            $table->index('triggered_at');

            // Foreign key para utilizador
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};