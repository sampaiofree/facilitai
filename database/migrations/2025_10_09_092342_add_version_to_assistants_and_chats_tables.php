<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('modelo');
        });

        Schema::table('chats', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('conv_id');
        });
    }

    public function down(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
