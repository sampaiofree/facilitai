<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_requests', function (Blueprint $table) {
            // Index para acelerar filtros por instância ordenando pelos mais recentes
            $table->index(['instance_id', 'created_at'], 'webhook_requests_instance_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_requests', function (Blueprint $table) {
            $table->dropIndex('webhook_requests_instance_created_at_idx');
        });
    }
};
