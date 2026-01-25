<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conexoes', function (Blueprint $table) {
            if (!Schema::hasColumn('conexoes', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('conexoes', 'informacoes')) {
                $table->text('informacoes')->nullable();
            }
            if (!Schema::hasColumn('conexoes', 'status')) {
                $table->string('status')->default('pendente');
            }
            if (!Schema::hasColumn('conexoes', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('conexoes', 'proxy_ip')) {
                $table->string('proxy_ip')->nullable();
            }
            if (!Schema::hasColumn('conexoes', 'proxy_port')) {
                $table->unsignedSmallInteger('proxy_port')->nullable();
            }
            if (!Schema::hasColumn('conexoes', 'proxy_username')) {
                $table->string('proxy_username')->nullable();
            }
            if (!Schema::hasColumn('conexoes', 'proxy_password')) {
                $table->string('proxy_password')->nullable();
            }
            if (!Schema::hasColumn('conexoes', 'proxy_provider')) {
                $table->string('proxy_provider')->nullable();
            }
            if (!Schema::hasColumn('conexoes', 'whatsapp_api_id')) {
                $table->foreignId('whatsapp_api_id')->nullable()->constrained('whatsapp_api')->nullOnDelete();
            }
            if (!Schema::hasColumn('conexoes', 'whatsapp_api_key')) {
                $table->string('whatsapp_api_key')->nullable();
            }
            if (!Schema::hasColumn('conexoes', 'model')) {
                $table->foreignId('model')->nullable()->constrained('iamodelos')->nullOnDelete();
            }
        });

        if (Schema::hasColumn('conexoes', 'iamodelo_id') && Schema::hasColumn('conexoes', 'model')) {
            DB::statement('UPDATE conexoes SET model = iamodelo_id WHERE model IS NULL');
        }

        if (Schema::hasColumn('conexoes', 'conexao_id')) {
            Schema::table('conexoes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('conexao_id');
            });
        }

        if (Schema::hasColumn('conexoes', 'conexao_key')) {
            Schema::table('conexoes', function (Blueprint $table) {
                $table->dropColumn('conexao_key');
            });
        }

        if (Schema::hasColumn('conexoes', 'iamodelo_id')) {
            Schema::table('conexoes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('iamodelo_id');
            });
        }

        if (Schema::hasColumn('conexoes', 'user_id')) {
            Schema::table('conexoes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        if (Schema::hasColumn('conexoes', 'ativo')) {
            Schema::table('conexoes', function (Blueprint $table) {
                $table->dropColumn('ativo');
            });
        }
    }

    public function down(): void
    {
        Schema::table('conexoes', function (Blueprint $table) {
            if (!Schema::hasColumn('conexoes', 'conexao_id')) {
                $table->foreignId('conexao_id')->nullable()->constrained('conexoes')->nullOnDelete();
            }
            if (!Schema::hasColumn('conexoes', 'conexao_key')) {
                $table->string('conexao_key')->nullable();
            }
            if (!Schema::hasColumn('conexoes', 'iamodelo_id')) {
                $table->foreignId('iamodelo_id')->nullable()->constrained('iamodelos')->nullOnDelete();
            }
            if (!Schema::hasColumn('conexoes', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('conexoes', 'ativo')) {
                $table->boolean('ativo')->default(true);
            }
        });

        if (Schema::hasColumn('conexoes', 'model') && Schema::hasColumn('conexoes', 'iamodelo_id')) {
            DB::statement('UPDATE conexoes SET iamodelo_id = model WHERE iamodelo_id IS NULL');
        }

        if (Schema::hasColumn('conexoes', 'whatsapp_api_id')) {
            Schema::table('conexoes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('whatsapp_api_id');
            });
        }

        if (Schema::hasColumn('conexoes', 'whatsapp_api_key')) {
            Schema::table('conexoes', function (Blueprint $table) {
                $table->dropColumn('whatsapp_api_key');
            });
        }

        if (Schema::hasColumn('conexoes', 'model')) {
            Schema::table('conexoes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('model');
            });
        }
    }
};
