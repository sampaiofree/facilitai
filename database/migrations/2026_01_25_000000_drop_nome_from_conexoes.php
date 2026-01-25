<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('conexoes', 'nome')) {
            DB::statement('DROP INDEX IF EXISTS conexoes_nome_unique');
            Schema::table('conexoes', function (Blueprint $table) {
                $table->dropColumn('nome');
            });
        }
    }

    public function down(): void
    {
        Schema::table('conexoes', function (Blueprint $table) {
            $table->string('nome', 50)->nullable();
        });
    }
};
