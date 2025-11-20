<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tokens_openai', function (Blueprint $table) {
            // 🔹 Primeiro garantimos que as colunas podem ser nulas
            $table->unsignedBigInteger('instance_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('credential_id')->nullable()->change();
        });

        // 🔹 Agora tratamos as foreign keys manualmente
        $this->dropForeignIfExists('tokens_openai', 'tokens_openai_instance_id_foreign');
        $this->dropForeignIfExists('tokens_openai', 'tokens_openai_user_id_foreign');
        $this->dropForeignIfExists('tokens_openai', 'tokens_openai_credential_id_foreign');

        Schema::table('tokens_openai', function (Blueprint $table) {
            $table->foreign('instance_id')
                ->references('id')->on('instances')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('credential_id')
                ->references('id')->on('credentials')
                ->nullOnDelete();
        });
    }

    private function dropForeignIfExists(string $table, string $foreignName): void
    {
        $exists = DB::select("SELECT CONSTRAINT_NAME
                              FROM information_schema.KEY_COLUMN_USAGE
                              WHERE TABLE_SCHEMA = DATABASE()
                                AND TABLE_NAME = ?
                                AND CONSTRAINT_NAME = ?",
                              [$table, $foreignName]);

        if (!empty($exists)) {
            Schema::table($table, function (Blueprint $table) use ($foreignName) {
                $table->dropForeign($foreignName);
            });
        }
    }

    public function down(): void
    {
        Schema::table('tokens_openai', function (Blueprint $table) {
            $table->dropForeign(['instance_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['credential_id']);
        });
    }
};
