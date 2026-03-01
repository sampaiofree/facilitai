<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_cloud_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_cloud_account_id')
                ->constrained('whatsapp_cloud_accounts')
                ->cascadeOnDelete();
            $table->foreignId('conexao_id')
                ->nullable()
                ->constrained('conexoes')
                ->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('template_name', 255);
            $table->string('language_code', 20)->default('pt_BR');
            $table->string('category', 30)->default('UTILITY');
            $table->json('variables')->nullable();
            $table->text('body_text')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'whatsapp_cloud_account_id']);
            $table->index(['user_id', 'conexao_id']);
            $table->unique(['whatsapp_cloud_account_id', 'template_name', 'language_code'], 'wct_account_template_language_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_cloud_templates');
    }
};

