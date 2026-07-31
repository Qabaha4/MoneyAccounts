<?php

use App\Helpers\PkSwapHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!PkSwapHelper::isIntegerColumn('users', 'id')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        $dependentTables = [
            ['table' => 'accounts', 'column' => 'user_id'],
            ['table' => 'transactions', 'column' => 'user_id'],
            ['table' => 'activities', 'column' => 'user_id'],
            ['table' => 'audit_logs', 'column' => 'user_id'],
        ];

        foreach ($dependentTables as $dep) {
            $fks = Schema::getForeignKeys($dep['table']);
            foreach ($fks as $fk) {
                if (
                    ($fk['columns'][0] ?? '') === $dep['column']
                    && ($fk['foreign_table'] ?? '') === 'users'
                ) {
                    Schema::table($dep['table'], function (Blueprint $t) use ($fk) {
                        $t->dropForeign($fk['name']);
                    });
                }
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('users')->whereNull('uuid')
            ->orderBy('created_at')->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('users')->where('id', $row->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        foreach ($dependentTables as $dep) {
            $legacyCol = $dep['column'] . '_legacy';
            if (Schema::hasColumn($dep['table'], $legacyCol)) {
                Schema::table($dep['table'], function (Blueprint $table) use ($legacyCol) {
                    $table->dropColumn($legacyCol);
                });
            }

            Schema::table($dep['table'], function (Blueprint $table) use ($dep, $legacyCol) {
                $table->renameColumn($dep['column'], $legacyCol);
            });

            Schema::table($dep['table'], function (Blueprint $table) use ($dep, $legacyCol) {
                $table->string($dep['column'], 36)->nullable()->after($legacyCol);
            });

            DB::table($dep['table'])
                ->join('users', "{$dep['table']}.{$legacyCol}", '=', 'users.id')
                ->update([
                    "{$dep['table']}.{$dep['column']}" => DB::raw('users.uuid'),
                ]);

            Schema::table($dep['table'], function (Blueprint $table) use ($dep, $legacyCol) {
                $table->string($dep['column'], 36)->nullable(false)->change();
                $table->dropColumn($legacyCol);
            });
        }

        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->string('user_id_legacy', 36)->nullable()->after('user_id');
            });

            DB::table('sessions')
                ->join('users', 'sessions.user_id', '=', 'users.id')
                ->update([
                    'sessions.user_id_legacy' => DB::raw('users.uuid'),
                ]);

            Schema::table('sessions', function (Blueprint $table) {
                $table->dropColumn('user_id');
                $table->renameColumn('user_id_legacy', 'user_id');
            });
        }

        if ($driver === 'sqlite') {
            $this->swapPkSqliteForUsers();
        } elseif ($driver === 'mysql') {
            Schema::withoutForeignKeyConstraints(function () {
                $this->swapPkMysql('users', 'uuid');
            });
        } elseif ($driver === 'pgsql') {
            $this->swapPkPostgres('users', 'uuid');
        }

        foreach ($dependentTables as $dep) {
            Schema::table($dep['table'], function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        throw new \RuntimeException('This migration is irreversible; restore from backup.');
    }

    private function swapPkSqliteForUsers(): void
    {
        Schema::create('users_new', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->string('remember_token', 100)->nullable();
            $t->text('two_factor_secret')->nullable();
            $t->text('two_factor_recovery_codes')->nullable();
            $t->timestamp('two_factor_confirmed_at')->nullable();
            $t->string('role')->default('user');
            $t->softDeletes();
            $t->timestamps();
        });

        DB::statement(
            'INSERT INTO users_new (id, name, email, email_verified_at, password, remember_token, ' .
            'two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, role, deleted_at, created_at, updated_at) ' .
            'SELECT uuid, name, email, email_verified_at, password, remember_token, ' .
            'two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, role, deleted_at, created_at, updated_at ' .
            'FROM users'
        );

        Schema::drop('users');
        Schema::rename('users_new', 'users');
    }

    private function swapPkMysql(string $table, string $newColumn): void
    {
        DB::statement("ALTER TABLE {$table} MODIFY id BIGINT UNSIGNED NOT NULL");
        Schema::table($table, fn (Blueprint $t) => $t->dropPrimary());

        Schema::table($table, function (Blueprint $t) use ($newColumn) {
            $t->renameColumn('id', 'legacy_id');
            $t->renameColumn($newColumn, 'id');
        });

        Schema::table($table, fn (Blueprint $t) => $t->primary('id'));
        Schema::table($table, fn (Blueprint $t) => $t->dropColumn('legacy_id'));
    }

    private function swapPkPostgres(string $table, string $newColumn): void
    {
        DB::statement("ALTER TABLE {$table} ALTER COLUMN id DROP DEFAULT");
        DB::statement("DROP SEQUENCE IF EXISTS {$table}_id_seq");
        Schema::table($table, fn (Blueprint $t) => $t->dropPrimary());

        Schema::table($table, function (Blueprint $t) use ($newColumn) {
            $t->renameColumn('id', 'legacy_id');
            $t->renameColumn($newColumn, 'id');
        });

        Schema::table($table, fn (Blueprint $t) => $t->primary('id'));
        Schema::table($table, fn (Blueprint $t) => $t->dropColumn('legacy_id'));
    }
};
