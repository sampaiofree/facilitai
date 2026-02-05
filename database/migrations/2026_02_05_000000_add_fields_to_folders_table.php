<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->foreignId('cliente_id')
                ->nullable()
                ->after('user_id')
                ->constrained('clientes')
                ->nullOnDelete();
            $table->unsignedInteger('storage_used_mb')->default(0)->after('name');
            $table->unsignedInteger('storage_limit_mb')->default(0)->after('storage_used_mb');
        });
    }

    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn(['cliente_id', 'storage_used_mb', 'storage_limit_mb']);
        });
    }
};
