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
        Schema::create('webhook_requests', function (Blueprint $table) {
            $table->id();
            $table->string('instance_id')->nullable()->index();
            $table->string('remote_jid')->nullable();
            $table->string('contact')->nullable()->index();
            $table->boolean('from_me')->nullable()->index();
            $table->string('message_type')->nullable()->index();
            $table->string('event_id')->nullable()->index();
            $table->unsignedBigInteger('message_timestamp')->nullable();
            $table->text('message_text')->nullable();
            $table->json('payload');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_requests');
    }
};
