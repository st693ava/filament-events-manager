<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['event_rule_id']);

            // Re-add the foreign key with cascade on delete
            $table->foreign('event_rule_id')->references('id')->on('event_rules')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            // Drop the cascade foreign key constraint
            $table->dropForeign(['event_rule_id']);

            // Re-add the original foreign key without cascade
            $table->foreign('event_rule_id')->references('id')->on('event_rules');
        });
    }
};