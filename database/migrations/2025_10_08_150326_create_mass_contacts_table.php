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
    Schema::create('mass_contacts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('campaign_id')->constrained('mass_campaigns')->onDelete('cascade');
        $table->string('numero', 20);
        $table->enum('status', ['pendente', 'enviado', 'falhou'])->default('pendente');
        $table->integer('tentativa')->default(0);
        $table->timestamp('enviado_em')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mass_contacts');
    }
};
