<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('uazapi_chat', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('phone')->nullable();
            $table->string('uazapi_instance_id');
            $table->boolean('bot_enabled')->default(true);
            $table->text('conv_id')->nullable();
            $table->text('version')->nullable();
            $table->text('informacoes')->nullable();
            $table->text('aguardando_atendimento')->nullable();
            $table->timestamps();

            $table->foreign('uazapi_instance_id')
                ->references('id')
                ->on('uazapi_instance')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uazapi_chat');
    }
};
