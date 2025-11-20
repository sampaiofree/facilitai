<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->text('prompt_notificar_adm')->nullable()->after('instructions');
            $table->text('prompt_buscar_get')->nullable()->after('prompt_notificar_adm');
            $table->text('prompt_enviar_media')->nullable()->after('prompt_buscar_get');
            $table->text('prompt_registrar_info_chat')->nullable()->after('prompt_enviar_media');
            $table->text('prompt_gerenciar_agenda')->nullable()->after('prompt_registrar_info_chat');
        });
    }

    public function down(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->dropColumn([
                'prompt_notificar_adm',
                'prompt_buscar_get',
                'prompt_enviar_media',
                'prompt_registrar_info_chat',
                'prompt_gerenciar_agenda',
            ]);
        });
    }
};
