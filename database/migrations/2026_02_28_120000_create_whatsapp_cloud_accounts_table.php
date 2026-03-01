<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_cloud_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone_number_id', 50);
            $table->string('business_account_id', 50)->nullable();
            $table->text('access_token');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
            $table->unique(['user_id', 'phone_number_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_cloud_accounts');
    }
};

