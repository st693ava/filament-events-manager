<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('trigger_type', ['eloquent', 'sql_query', 'custom', 'schedule'])->default('eloquent');
            $table->json('trigger_config'); // Model class, events, table, etc.
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Ordem de execução

            // Auditoria
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();

            $table->timestamps();

            // Índices para performance
            $table->index(['is_active', 'priority']);
            $table->index('trigger_type');

            // Foreign keys para utilizadores (se existir tabela users)
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_rules');
    }
};