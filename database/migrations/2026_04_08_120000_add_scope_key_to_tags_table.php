<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->string('scope_key')->nullable()->after('cliente_id');
        });

        DB::table('tags')
            ->select(['id', 'user_id', 'cliente_id'])
            ->orderBy('id')
            ->chunkById(100, function ($tags): void {
                foreach ($tags as $tag) {
                    $scopeKey = $tag->cliente_id
                        ? sprintf('user:%d:cliente:%d', $tag->user_id, $tag->cliente_id)
                        : sprintf('user:%d:global', $tag->user_id);

                    DB::table('tags')
                        ->where('id', $tag->id)
                        ->update(['scope_key' => $scopeKey]);
                }
            });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique('tags_user_id_name_unique');
            $table->unique(['scope_key', 'name'], 'tags_scope_key_name_unique');
            $table->index(['user_id', 'cliente_id', 'name'], 'tags_user_cliente_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('tags_user_cliente_name_index');
            $table->dropUnique('tags_scope_key_name_unique');
            $table->dropColumn('scope_key');
            $table->unique(['user_id', 'name']);
        });
    }
};
