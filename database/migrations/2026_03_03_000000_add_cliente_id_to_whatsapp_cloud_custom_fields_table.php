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
            if (!Schema::hasColumn('whatsapp_cloud_custom_fields', 'cliente_id')) {
                $table->foreignId('cliente_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('clientes')
                    ->nullOnDelete();
                $table->index(['user_id', 'cliente_id'], 'wccf_user_cliente_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('whatsapp_cloud_custom_fields') || !Schema::hasColumn('whatsapp_cloud_custom_fields', 'cliente_id')) {
            return;
        }

        Schema::table('whatsapp_cloud_custom_fields', function (Blueprint $table) {
            $table->dropIndex('wccf_user_cliente_idx');
            $table->dropForeign(['cliente_id']);
            $table->dropColumn('cliente_id');
        });
    }
};

