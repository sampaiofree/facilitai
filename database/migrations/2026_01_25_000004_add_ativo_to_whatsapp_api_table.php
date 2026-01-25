<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_api', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_api', 'ativo')) {
                $table->boolean('ativo')->default(true)->after('slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_api', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_api', 'ativo')) {
                $table->dropColumn('ativo');
            }
        });
    }
};
