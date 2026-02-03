<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('sequence_chats')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSequenceChatsForSqlite();
            return;
        }

        if ($this->constraintExists('sequence_chats', 'sequence_chats_chat_id_foreign')) {
            Schema::table('sequence_chats', function (Blueprint $table) {
                $table->dropForeign('sequence_chats_chat_id_foreign');
            });
        }

        if ($this->indexExists('sequence_chats', 'sequence_chats_sequence_id_chat_id_unique')) {
            Schema::table('sequence_chats', function (Blueprint $table) {
                $table->dropUnique('sequence_chats_sequence_id_chat_id_unique');
            });
        }

        if (Schema::hasColumn('sequence_chats', 'chat_id')) {
            Schema::table('sequence_chats', function (Blueprint $table) {
                $table->dropColumn('chat_id');
            });
        }

        if (Schema::hasColumn('sequence_chats', 'sequence_id') && Schema::hasColumn('sequence_chats', 'cliente_lead_id')) {
            if (!$this->indexExists('sequence_chats', 'sequence_chats_sequence_id_cliente_lead_id_unique')) {
                Schema::table('sequence_chats', function (Blueprint $table) {
                    $table->unique(['sequence_id', 'cliente_lead_id']);
                });
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('sequence_chats')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->restoreSequenceChatsForSqlite();
            return;
        }

        if ($this->indexExists('sequence_chats', 'sequence_chats_sequence_id_cliente_lead_id_unique')) {
            Schema::table('sequence_chats', function (Blueprint $table) {
                $table->dropUnique('sequence_chats_sequence_id_cliente_lead_id_unique');
            });
        }

        if (!Schema::hasColumn('sequence_chats', 'chat_id')) {
            Schema::table('sequence_chats', function (Blueprint $table) {
                $table->foreignId('chat_id')->nullable()->index();
            });
        }

        if (Schema::hasTable('chats')
            && Schema::hasColumn('sequence_chats', 'chat_id')
            && !$this->constraintExists('sequence_chats', 'sequence_chats_chat_id_foreign')) {
            Schema::table('sequence_chats', function (Blueprint $table) {
                $table->foreign('chat_id')->references('id')->on('chats')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('sequence_chats', 'sequence_id') && Schema::hasColumn('sequence_chats', 'chat_id')) {
            if (!$this->indexExists('sequence_chats', 'sequence_chats_sequence_id_chat_id_unique')) {
                Schema::table('sequence_chats', function (Blueprint $table) {
                    $table->unique(['sequence_id', 'chat_id']);
                });
            }
        }
    }

    private function constraintExists(string $table, string $constraintName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return false;
        }

        $rows = DB::select(
            'select 1 from information_schema.table_constraints where table_name = ? and constraint_name = ? limit 1',
            [$table, $constraintName]
        );

        if (empty($rows) && $driver === 'pgsql') {
            $rows = DB::select(
                'select 1 from information_schema.table_constraints where lower(table_name) = lower(?) and lower(constraint_name) = lower(?) limit 1',
                [$table, $constraintName]
            );
        }

        return !empty($rows);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return false;
        }

        if ($driver === 'pgsql') {
            $rows = DB::select(
                'select 1 from pg_indexes where lower(tablename) = lower(?) and lower(indexname) = lower(?) limit 1',
                [$table, $indexName]
            );

            return !empty($rows);
        }

        $rows = DB::select(
            'select 1 from information_schema.statistics where table_schema = database() and table_name = ? and index_name = ? limit 1',
            [$table, $indexName]
        );

        return !empty($rows);
    }

    private function rebuildSequenceChatsForSqlite(): void
    {
        $tempTable = 'sequence_chats_old_' . time();
        $columnsToCopy = [
            'id',
            'sequence_id',
            'cliente_lead_id',
            'passo_atual_id',
            'status',
            'iniciado_em',
            'proximo_envio_em',
            'criado_por',
            'created_at',
            'updated_at',
        ];

        $existingColumns = Schema::getColumnListing('sequence_chats');
        $copyColumns = array_values(array_intersect($columnsToCopy, $existingColumns));

        Schema::disableForeignKeyConstraints();
        DB::statement('PRAGMA foreign_keys=OFF');

        if (Schema::hasTable($tempTable)) {
            Schema::drop($tempTable);
        }

        Schema::rename('sequence_chats', $tempTable);

        Schema::create('sequence_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('sequences')->cascadeOnDelete();
            $table->foreignId('cliente_lead_id')->nullable()->constrained('cliente_lead')->nullOnDelete();
            $table->foreignId('passo_atual_id')->nullable()->constrained('sequence_steps')->nullOnDelete();
            $table->enum('status', ['em_andamento', 'concluida', 'pausada', 'cancelada'])->default('em_andamento');
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('proximo_envio_em')->nullable();
            $table->string('criado_por')->nullable();
            $table->timestamps();
            $table->unique(['sequence_id', 'cliente_lead_id']);
        });

        if (!empty($copyColumns)) {
            $columnList = implode(', ', $copyColumns);
            DB::statement("insert into sequence_chats ({$columnList}) select {$columnList} from {$tempTable}");
        }

        Schema::drop($tempTable);

        DB::statement('PRAGMA foreign_keys=ON');
        Schema::enableForeignKeyConstraints();
    }

    private function restoreSequenceChatsForSqlite(): void
    {
        $tempTable = 'sequence_chats_old_' . time();
        $columnsToCopy = [
            'id',
            'sequence_id',
            'chat_id',
            'cliente_lead_id',
            'passo_atual_id',
            'status',
            'iniciado_em',
            'proximo_envio_em',
            'criado_por',
            'created_at',
            'updated_at',
        ];

        $existingColumns = Schema::getColumnListing('sequence_chats');
        $copyColumns = array_values(array_intersect($columnsToCopy, $existingColumns));

        Schema::disableForeignKeyConstraints();
        DB::statement('PRAGMA foreign_keys=OFF');

        if (Schema::hasTable($tempTable)) {
            Schema::drop($tempTable);
        }

        Schema::rename('sequence_chats', $tempTable);

        Schema::create('sequence_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('sequences')->cascadeOnDelete();
            $table->foreignId('chat_id')->nullable()->index();
            $table->foreignId('cliente_lead_id')->nullable()->constrained('cliente_lead')->nullOnDelete();
            $table->foreignId('passo_atual_id')->nullable()->constrained('sequence_steps')->nullOnDelete();
            $table->enum('status', ['em_andamento', 'concluida', 'pausada', 'cancelada'])->default('em_andamento');
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('proximo_envio_em')->nullable();
            $table->string('criado_por')->nullable();
            $table->timestamps();
            $table->unique(['sequence_id', 'chat_id']);
        });

        if (!empty($copyColumns)) {
            $columnList = implode(', ', $copyColumns);
            DB::statement("insert into sequence_chats ({$columnList}) select {$columnList} from {$tempTable}");
        }

        Schema::drop($tempTable);

        DB::statement('PRAGMA foreign_keys=ON');
        Schema::enableForeignKeyConstraints();
    }
};
