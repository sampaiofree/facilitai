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
            if (!Schema::hasColumn('conexoes', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });

        if (Schema::hasColumn('conexoes', 'is_active')) {
            DB::table('conexoes')
                ->whereNull('is_active')
                ->update(['is_active' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('conexoes', function (Blueprint $table) {
            if (Schema::hasColumn('conexoes', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
