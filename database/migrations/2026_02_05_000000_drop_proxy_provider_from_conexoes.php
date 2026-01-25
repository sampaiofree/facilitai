<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conexoes', function (Blueprint $table) {
            if (Schema::hasColumn('conexoes', 'proxy_provider')) {
                $table->dropColumn('proxy_provider');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conexoes', function (Blueprint $table) {
            if (!Schema::hasColumn('conexoes', 'proxy_provider')) {
                $table->string('proxy_provider')->nullable();
            }
        });
    }
};
