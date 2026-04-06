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
        Schema::table('kommo_accounts', function (Blueprint $table) {
            $table->string('pipeline_id', 50)->nullable()->after('kommo_account_name');
            $table->string('pipeline_name')->nullable()->after('pipeline_id');
            $table->string('status_id', 50)->nullable()->after('pipeline_name');
            $table->string('status_name')->nullable()->after('status_id');
        });

        $rows = DB::table('kommo_accounts')
            ->select(['id', 'user_id', 'name'])
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $base = Str::slug((string) $row->name);

            if ($base === '') {
                $base = 'kommo-account';
            }

            $candidate = Str::limit($base, 191, '');
            $exists = DB::table('kommo_accounts')
                ->where('user_id', $row->user_id)
                ->where('name', $candidate)
                ->where('id', '!=', $row->id)
                ->exists();

            if ($exists) {
                $suffix = '-' . $row->id;
                $candidate = Str::limit($base, 191 - strlen($suffix), '') . $suffix;
            }

            DB::table('kommo_accounts')
                ->where('id', $row->id)
                ->update(['name' => $candidate]);
        }

        Schema::table('kommo_accounts', function (Blueprint $table) {
            $table->dropUnique('kommo_accounts_cliente_subdomain_unique');
            $table->unique(['user_id', 'name'], 'kommo_accounts_user_name_unique');
            $table->index('subdomain', 'kommo_accounts_subdomain_idx');
        });
    }

    public function down(): void
    {
        Schema::table('kommo_accounts', function (Blueprint $table) {
            $table->dropUnique('kommo_accounts_user_name_unique');
            $table->dropIndex('kommo_accounts_subdomain_idx');
            $table->dropColumn([
                'pipeline_id',
                'pipeline_name',
                'status_id',
                'status_name',
            ]);
        });

        Schema::table('kommo_accounts', function (Blueprint $table) {
            $table->unique(['cliente_id', 'subdomain'], 'kommo_accounts_cliente_subdomain_unique');
        });
    }
};
