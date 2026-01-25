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
            $table->string('proxy_ip')->nullable()->after('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uazapi_instance', function (Blueprint $table) {
            $table->dropColumn('proxy_ip');
        });
    }
};
