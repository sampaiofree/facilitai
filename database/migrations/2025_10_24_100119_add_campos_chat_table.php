<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->string('nome')->nullable()->after('contact');
            $table->text('informacoes')->nullable()->after('nome');
            $table->boolean('aguardando_atendimento')->default(false)->after('informacoes');
        });
    }

    public function down()
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn(['nome', 'informacoes', 'aguardando_atendimento']);
        });
    }
};
