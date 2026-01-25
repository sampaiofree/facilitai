<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->text('prompt_aplicar_tags')->nullable()->after('prompt_gerenciar_agenda');
        });
    }

    public function down(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->dropColumn('prompt_aplicar_tags');
        });
    }
};
