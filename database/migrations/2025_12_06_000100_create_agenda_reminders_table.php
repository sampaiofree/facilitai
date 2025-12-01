<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained()->cascadeOnDelete();
            $table->foreignId('disponibilidade_id')->constrained()->cascadeOnDelete();
            $table->string('telefone');
            $table->unsignedBigInteger('instance_id');
            $table->text('mensagem_template')->nullable();
            $table->integer('offset_minutos')->default(-1440);
            $table->dateTime('agendado_em');
            $table->dateTime('disparo_em');
            $table->string('status')->default('pendente');
            $table->unsignedInteger('tentativas')->default(0);
            $table->text('last_error')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['disponibilidade_id', 'offset_minutos'], 'agenda_reminders_disponibilidade_offset_unique');
            $table->index(['status', 'disparo_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_reminders');
    }
};
