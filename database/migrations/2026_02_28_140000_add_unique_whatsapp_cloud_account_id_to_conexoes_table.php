<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('conexoes', 'whatsapp_cloud_account_id')) {
            return;
        }

        $this->normalizeExistingDuplicates();

        Schema::table('conexoes', function (Blueprint $table) {
            $table->unique('whatsapp_cloud_account_id', 'conexoes_whatsapp_cloud_account_id_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('conexoes', 'whatsapp_cloud_account_id')) {
            return;
        }

        Schema::table('conexoes', function (Blueprint $table) {
            $table->dropUnique('conexoes_whatsapp_cloud_account_id_unique');
        });
    }

    private function normalizeExistingDuplicates(): void
    {
        $duplicates = DB::table('conexoes')
            ->select('whatsapp_cloud_account_id')
            ->whereNotNull('whatsapp_cloud_account_id')
            ->groupBy('whatsapp_cloud_account_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('whatsapp_cloud_account_id');

        foreach ($duplicates as $accountId) {
            $idsToKeep = DB::table('conexoes')
                ->where('whatsapp_cloud_account_id', $accountId)
                ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                ->orderByDesc('id')
                ->limit(1)
                ->pluck('id');

            $keepId = $idsToKeep->first();

            DB::table('conexoes')
                ->where('whatsapp_cloud_account_id', $accountId)
                ->when($keepId !== null, fn ($query) => $query->where('id', '!=', $keepId))
                ->update(['whatsapp_cloud_account_id' => null]);
        }
    }
};
