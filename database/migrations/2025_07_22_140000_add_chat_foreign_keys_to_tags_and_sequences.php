<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Only add the constraints after `chats` exists.
        if (!Schema::hasTable('chats')) {
            return;
        }

        if (Schema::hasTable('chat_tag') && !$this->constraintExists('chat_tag', 'chat_tag_chat_id_foreign')) {
            Schema::table('chat_tag', function (Blueprint $table) {
                $table->foreign('chat_id')->references('id')->on('chats')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('sequence_chats') && !$this->constraintExists('sequence_chats', 'sequence_chats_chat_id_foreign')) {
            Schema::table('sequence_chats', function (Blueprint $table) {
                $table->foreign('chat_id')->references('id')->on('chats')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chat_tag') && $this->constraintExists('chat_tag', 'chat_tag_chat_id_foreign')) {
            Schema::table('chat_tag', function (Blueprint $table) {
                $table->dropForeign('chat_tag_chat_id_foreign');
            });
        }

        if (Schema::hasTable('sequence_chats') && $this->constraintExists('sequence_chats', 'sequence_chats_chat_id_foreign')) {
            Schema::table('sequence_chats', function (Blueprint $table) {
                $table->dropForeign('sequence_chats_chat_id_foreign');
            });
        }
    }

    private function constraintExists(string $table, string $constraintName): bool
    {
        $driver = DB::getDriverName();

        // SQLite doesn't expose information_schema.
        if ($driver === 'sqlite') {
            return false;
        }

        // information_schema works for both pgsql and mysql/mariadb.
        $rows = DB::select(
            'select 1 from information_schema.table_constraints where table_name = ? and constraint_name = ? limit 1',
            [$table, $constraintName]
        );

        // On some drivers, table names may be case sensitive. Fall back to a
        // case-insensitive check when nothing was found.
        if (empty($rows) && $driver === 'pgsql') {
            $rows = DB::select(
                'select 1 from information_schema.table_constraints where lower(table_name) = lower(?) and lower(constraint_name) = lower(?) limit 1',
                [$table, $constraintName]
            );
        }

        return !empty($rows);
    }
};
