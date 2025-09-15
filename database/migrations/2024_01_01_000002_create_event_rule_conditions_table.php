<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_rule_id')->constrained('event_rules')->cascadeOnDelete();

            // Campo a verificar (ex: user.email, order.total, status)
            $table->string('field_path');

            // Operador de comparação
            $table->enum('operator', [
                '=', '!=', '>', '<', '>=', '<=',
                'contains', 'starts_with', 'ends_with', 'in', 'not_in',
                'changed', 'was'
            ]);

            // Valor para comparação (JSON para valores complexos)
            $table->text('value')->nullable();

            // Tipo do valor
            $table->enum('value_type', ['static', 'dynamic', 'model_field'])->default('static');

            // Operador lógico para ligar com próxima condição
            $table->enum('logical_operator', ['AND', 'OR'])->default('AND');

            // Agrupamento de condições
            $table->string('group_id')->nullable();

            // Ordem das condições
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // Índices para performance
            $table->index(['event_rule_id', 'group_id']);
            $table->index(['field_path', 'operator']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_rule_conditions');
    }
};