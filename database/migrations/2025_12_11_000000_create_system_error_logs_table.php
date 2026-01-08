<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('context')->nullable();
            $table->string('function_name')->nullable();
            $table->text('message');
            $table->unsignedBigInteger('instance_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('chat_id')->nullable();
            $table->string('conversation_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_error_logs');
    }
};
