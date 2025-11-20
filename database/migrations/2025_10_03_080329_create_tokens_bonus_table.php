<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTokensBonusTable extends Migration
{
    public function up()
    {
        Schema::create('tokens_bonus', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('informacoes')->nullable(); // campo de texto
            $table->unsignedBigInteger('tokens'); // número de tokens
            $table->date('inicio'); // data de liberação
            $table->date('fim');    // data de expiração
            $table->string('hotmart')->nullable(); // campo hotmart (pode ser null)

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tokens_bonus');
    }
}
