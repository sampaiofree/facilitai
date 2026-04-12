<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conexoes', function (Blueprint $table) {
            if (!Schema::hasColumn('conexoes', 'permitiredicao')) {
                $table->boolean('permitiredicao')->default(false);
            }
        });

        if (Schema::hasColumn('conexoes', 'permitiredicao')) {
            DB::table('conexoes')
                ->whereNull('permitiredicao')
                ->update(['permitiredicao' => false]);
        }
    }

    public function down(): void
    {
        Schema::table('conexoes', function (Blueprint $table) {
            if (Schema::hasColumn('conexoes', 'permitiredicao')) {
                $table->dropColumn('permitiredicao');
            }
        });
    }
};
