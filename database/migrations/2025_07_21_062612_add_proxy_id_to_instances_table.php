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
        Schema::table('instances', function (Blueprint $table) {
            // Adiciona as colunas de proxy após a coluna 'default_assistant_id'
            $table->string('proxy_ip')->nullable()->after('default_assistant_id');
            $table->string('proxy_port')->nullable()->after('proxy_ip');
            $table->text('proxy_username')->nullable()->after('proxy_port')->comment('Encrypted');
            $table->text('proxy_password')->nullable()->after('proxy_username')->comment('Encrypted');
            $table->string('proxy_provider')->nullable()->after('proxy_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->dropColumn([
                'proxy_ip',
                'proxy_port',
                'proxy_username',
                'proxy_password',
                'proxy_provider',
            ]);
        });
    }
};