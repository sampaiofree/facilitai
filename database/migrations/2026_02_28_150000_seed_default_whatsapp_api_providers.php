<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        $providers = [
            [
                'nome' => 'Uazapi',
                'descricao' => 'Integração não oficial via Uazapi.',
                'slug' => 'uazapi',
                'ativo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nome' => 'API Oficial',
                'descricao' => 'Integração oficial da Meta via provedor oficial.',
                'slug' => 'api_oficial',
                'ativo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nome' => 'WhatsApp Cloud',
                'descricao' => 'WhatsApp Cloud API (Meta).',
                'slug' => 'whatsapp_cloud',
                'ativo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('whatsapp_api')->upsert(
            $providers,
            ['slug'],
            ['nome', 'descricao', 'ativo', 'updated_at']
        );
    }

    public function down(): void
    {
        // Keep data on rollback to avoid removing providers in active environments.
    }
};
