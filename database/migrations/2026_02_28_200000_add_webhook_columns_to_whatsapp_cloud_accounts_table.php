<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_cloud_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_cloud_accounts', 'app_id')) {
                $table->string('app_id', 80)->nullable()->after('business_account_id');
            }

            if (!Schema::hasColumn('whatsapp_cloud_accounts', 'app_secret')) {
                $table->text('app_secret')->nullable()->after('app_id');
            }

            if (!Schema::hasColumn('whatsapp_cloud_accounts', 'webhook_key')) {
                $table->string('webhook_key', 120)->nullable()->after('app_secret');
            }

            if (!Schema::hasColumn('whatsapp_cloud_accounts', 'webhook_verify_token')) {
                $table->string('webhook_verify_token', 120)->nullable()->after('webhook_key');
            }
        });

        $this->backfillWebhookColumns();

        Schema::table('whatsapp_cloud_accounts', function (Blueprint $table) {
            $table->unique('webhook_key', 'wca_webhook_key_unique');
            $table->unique('webhook_verify_token', 'wca_webhook_verify_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_cloud_accounts', function (Blueprint $table) {
            $table->dropUnique('wca_webhook_key_unique');
            $table->dropUnique('wca_webhook_verify_token_unique');

            if (Schema::hasColumn('whatsapp_cloud_accounts', 'webhook_verify_token')) {
                $table->dropColumn('webhook_verify_token');
            }

            if (Schema::hasColumn('whatsapp_cloud_accounts', 'webhook_key')) {
                $table->dropColumn('webhook_key');
            }

            if (Schema::hasColumn('whatsapp_cloud_accounts', 'app_secret')) {
                $table->dropColumn('app_secret');
            }

            if (Schema::hasColumn('whatsapp_cloud_accounts', 'app_id')) {
                $table->dropColumn('app_id');
            }
        });
    }

    private function backfillWebhookColumns(): void
    {
        $rows = DB::table('whatsapp_cloud_accounts')
            ->select('id', 'webhook_key', 'webhook_verify_token')
            ->get();

        foreach ($rows as $row) {
            $updates = [];

            $currentWebhookKey = trim((string) ($row->webhook_key ?? ''));
            if ($currentWebhookKey === '') {
                $updates['webhook_key'] = $this->generateUniqueToken('webhook_key', 'wck_');
            }

            $currentVerifyToken = trim((string) ($row->webhook_verify_token ?? ''));
            if ($currentVerifyToken === '') {
                $updates['webhook_verify_token'] = $this->generateUniqueToken('webhook_verify_token', 'wvt_');
            }

            if (!empty($updates)) {
                DB::table('whatsapp_cloud_accounts')
                    ->where('id', $row->id)
                    ->update($updates);
            }
        }
    }

    private function generateUniqueToken(string $column, string $prefix): string
    {
        do {
            $token = $prefix . Str::lower(Str::random(48));
        } while (DB::table('whatsapp_cloud_accounts')->where($column, $token)->exists());

        return $token;
    }
};
