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
    Schema::create('credentials', function (Blueprint $table) {
        $table->id();
        // Chave estrangeira para o usuário, se o usuário for deletado, suas credenciais também serão.
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('name'); // Ex: "OpenAI Chave Principal", "Gemini API"
        $table->text('token')->comment('Encrypted'); // Usamos text para tokens longos
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
