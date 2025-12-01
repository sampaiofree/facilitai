<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->boolean('reminder_24h')->default(false)->after('limite_por_horario');
            $table->boolean('reminder_2h')->default(false)->after('reminder_24h');
            $table->text('reminder_template')->nullable()->after('reminder_2h');
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn(['reminder_24h', 'reminder_2h', 'reminder_template']);
        });
    }
};
