<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kommo_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('name');
            $table->string('subdomain', 191);
            $table->text('access_token');
            $table->string('kommo_account_id', 50)->nullable();
            $table->string('kommo_account_name')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->text('last_verification_error')->nullable();
            $table->timestamps();

            $table->unique(['cliente_id', 'subdomain'], 'kommo_accounts_cliente_subdomain_unique');
            $table->index(['user_id', 'cliente_id'], 'kommo_accounts_user_cliente_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kommo_accounts');
    }
};
