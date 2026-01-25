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
        Schema::table('uazapi_instance', function (Blueprint $table) {
            $table->unsignedBigInteger('assistant_id')->nullable()->after('user_id');
            $table->foreign('assistant_id')->references('id')->on('assistants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uazapi_instance', function (Blueprint $table) {
            $table->dropForeign(['assistant_id']);
            $table->dropColumn('assistant_id');
        });
    }
};
