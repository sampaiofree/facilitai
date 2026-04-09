<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_cloud_custom_fields')) {
            return;
        }

        Schema::table('whatsapp_cloud_custom_fields', function (Blueprint $table) {
            $table->dropUnique('wccf_user_name_unique');
            $table->unique(['cliente_id', 'name'], 'wccf_cliente_name_unique');
            $table->index(['user_id', 'name'], 'wccf_user_name_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('whatsapp_cloud_custom_fields')) {
            return;
        }

        Schema::table('whatsapp_cloud_custom_fields', function (Blueprint $table) {
            $table->dropIndex('wccf_user_name_idx');
            $table->dropUnique('wccf_cliente_name_unique');
            $table->unique(['user_id', 'name'], 'wccf_user_name_unique');
        });
    }
};
