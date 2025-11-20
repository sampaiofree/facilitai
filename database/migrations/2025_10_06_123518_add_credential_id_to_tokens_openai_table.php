<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('tokens_openai', function (Blueprint $table) {
        $table->unsignedBigInteger('credential_id')->nullable()->after('instance_id');
        $table->foreign('credential_id')->references('id')->on('credentials')->onDelete('set null');
    });
}

public function down()
{
    Schema::table('tokens_openai', function (Blueprint $table) {
        $table->dropForeign(['credential_id']);
        $table->dropColumn('credential_id');
    });
}

};
