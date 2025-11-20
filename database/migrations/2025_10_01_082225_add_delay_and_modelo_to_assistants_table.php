<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->integer('delay')->nullable()->after('developerPrompt'); // Campo inteiro, opcional
            $table->string('modelo', 50)->nullable()->after('delay'); // Campo string, limitado a 50 caracteres, opcional
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->dropColumn('delay');
            $table->dropColumn('modelo');
        });
    }
};