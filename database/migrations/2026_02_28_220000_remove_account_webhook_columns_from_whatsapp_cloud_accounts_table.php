<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A partir desta migração o webhook é apenas por usuário.
        $this->dropUniqueIfExists('whatsapp_cloud_accounts', 'wca_webhook_key_unique');
        $this->dropUniqueIfExists('whatsapp_cloud_accounts', 'wca_webhook_verify_token_unique');

        Schema::table('whatsapp_cloud_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_cloud_accounts', 'webhook_verify_token')) {
                $table->dropColumn('webhook_verify_token');
            }

            if (Schema::hasColumn('whatsapp_cloud_accounts', 'webhook_key')) {
                $table->dropColumn('webhook_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_cloud_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_cloud_accounts', 'webhook_key')) {
                $table->string('webhook_key', 120)->nullable()->after('app_secret');
            }

            if (!Schema::hasColumn('whatsapp_cloud_accounts', 'webhook_verify_token')) {
                $table->string('webhook_verify_token', 120)->nullable()->after('webhook_key');
            }
        });

        Schema::table('whatsapp_cloud_accounts', function (Blueprint $table) {
            $table->unique('webhook_key', 'wca_webhook_key_unique');
            $table->unique('webhook_verify_token', 'wca_webhook_verify_token_unique');
        });
    }

    private function dropUniqueIfExists(string $table, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
                $blueprint->dropUnique($indexName);
            });
        } catch (\Throwable) {
            // Se o índice já não existir, segue a migração.
        }
    }
};
