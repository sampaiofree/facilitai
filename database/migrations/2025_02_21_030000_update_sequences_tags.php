<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sequences', function (Blueprint $table) {
            $table->json('tags_incluir')->nullable()->after('active');
            $table->json('tags_excluir')->nullable()->after('tags_incluir');
        });

        Schema::table('sequence_chats', function (Blueprint $table) {
            if (Schema::hasColumn('sequence_chats', 'tags_incluir')) {
                $table->dropColumn('tags_incluir');
            }
            if (Schema::hasColumn('sequence_chats', 'tags_excluir')) {
                $table->dropColumn('tags_excluir');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sequences', function (Blueprint $table) {
            $table->dropColumn('tags_incluir');
            $table->dropColumn('tags_excluir');
        });

        Schema::table('sequence_chats', function (Blueprint $table) {
            $table->json('tags_incluir')->nullable();
            $table->json('tags_excluir')->nullable();
        });
    }
};
