<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('chat_tag')) {
            Schema::dropIfExists('chat_tag');
        }

        if (Schema::hasTable('chats')) {
            Schema::dropIfExists('chats');
        }

        if (Schema::hasTable('disponibilidades') && Schema::hasColumn('disponibilidades', 'chat_id')) {
            Schema::table('disponibilidades', function (Blueprint $table) {
                $table->dropForeign(['chat_id']);
                $table->dropColumn('chat_id');
            });
        }

        if (Schema::hasTable('mass_contacts') && Schema::hasColumn('mass_contacts', 'chat_id')) {
            Schema::table('mass_contacts', function (Blueprint $table) {
                $table->dropForeign(['chat_id']);
                $table->dropColumn('chat_id');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('chats')) {
            Schema::create('chats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('instance_id')->constrained()->cascadeOnDelete();
                $table->string('contact')->comment('Número do contato no WhatsApp');
                $table->string('assistant_id')->comment('ID do assistente OpenAI');
                $table->string('thread_id')->nullable()->comment('ID do thread da conversa na OpenAI');
                $table->boolean('bot_enabled')->default(true);
                $table->timestamps();
                $table->unique(['instance_id', 'contact']);
            });
        }

        if (!Schema::hasTable('chat_tag')) {
            Schema::create('chat_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_id')->index();
                $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
                $table->foreignId('applied_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('origem')->nullable();
                $table->timestamps();
                $table->unique(['chat_id', 'tag_id']);
            });
        }

        if (Schema::hasTable('disponibilidades') && !Schema::hasColumn('disponibilidades', 'chat_id')) {
            Schema::table('disponibilidades', function (Blueprint $table) {
                $table->foreignId('chat_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        if (Schema::hasTable('mass_contacts') && !Schema::hasColumn('mass_contacts', 'chat_id')) {
            Schema::table('mass_contacts', function (Blueprint $table) {
                $table->foreignId('chat_id')
                    ->nullable()
                    ->after('campaign_id')
                    ->constrained('chats')
                    ->nullOnDelete();
            });
        }
    }
};
