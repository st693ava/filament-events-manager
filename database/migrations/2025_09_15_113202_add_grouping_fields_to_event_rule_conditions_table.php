<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_rule_conditions', function (Blueprint $table) {
            // Campos para agrupamento de condições com parêntesis
            $table->string('group_start')->nullable()->after('field_path'); // '(', '((', '((('
            $table->string('group_end')->nullable()->after('logical_operator'); // ')', '))', ')))'

            // Prioridade para ordem de avaliação
            $table->integer('priority')->default(0)->after('group_end');
        });
    }

    public function down(): void
    {
        Schema::table('event_rule_conditions', function (Blueprint $table) {
            $table->dropColumn(['group_start', 'group_end', 'priority']);
        });
    }
};
