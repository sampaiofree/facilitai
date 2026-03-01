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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'whatsapp_cloud_webhook_key')) {
                $table->string('whatsapp_cloud_webhook_key', 120)->nullable()->after('remember_token');
            }

            if (!Schema::hasColumn('users', 'whatsapp_cloud_webhook_verify_token')) {
                $table->string('whatsapp_cloud_webhook_verify_token', 120)->nullable()->after('whatsapp_cloud_webhook_key');
            }
        });

        $this->backfillWebhookColumns();

        Schema::table('users', function (Blueprint $table) {
            $table->unique('whatsapp_cloud_webhook_key', 'users_wac_webhook_key_unique');
            $table->unique('whatsapp_cloud_webhook_verify_token', 'users_wac_webhook_verify_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_wac_webhook_key_unique');
            $table->dropUnique('users_wac_webhook_verify_token_unique');

            if (Schema::hasColumn('users', 'whatsapp_cloud_webhook_verify_token')) {
                $table->dropColumn('whatsapp_cloud_webhook_verify_token');
            }

            if (Schema::hasColumn('users', 'whatsapp_cloud_webhook_key')) {
                $table->dropColumn('whatsapp_cloud_webhook_key');
            }
        });
    }

    private function backfillWebhookColumns(): void
    {
        $users = DB::table('users')
            ->select('id', 'whatsapp_cloud_webhook_key', 'whatsapp_cloud_webhook_verify_token')
            ->get();

        foreach ($users as $user) {
            $updates = [];

            if (trim((string) ($user->whatsapp_cloud_webhook_key ?? '')) === '') {
                $updates['whatsapp_cloud_webhook_key'] = $this->generateUniqueToken('whatsapp_cloud_webhook_key', 'wcu_');
            }

            if (trim((string) ($user->whatsapp_cloud_webhook_verify_token ?? '')) === '') {
                $updates['whatsapp_cloud_webhook_verify_token'] = $this->generateUniqueToken('whatsapp_cloud_webhook_verify_token', 'wvu_');
            }

            if (!empty($updates)) {
                DB::table('users')->where('id', $user->id)->update($updates);
            }
        }
    }

    private function generateUniqueToken(string $column, string $prefix): string
    {
        do {
            $token = $prefix . Str::lower(Str::random(48));
        } while (DB::table('users')->where($column, $token)->exists());

        return $token;
    }
};
