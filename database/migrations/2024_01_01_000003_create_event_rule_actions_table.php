<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_rule_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_rule_id')->constrained('event_rules')->cascadeOnDelete();

            // Tipo de ação (email, webhook, activity_log, etc.)
            $table->string('action_type', 100);

            // Configuração específica da ação (JSON)
            $table->json('action_config');

            // Ordem de execução
            $table->integer('sort_order')->default(0);

            // Se a ação está ativa
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Índices para performance
            $table->index(['event_rule_id', 'sort_order']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_rule_actions');
    }
};