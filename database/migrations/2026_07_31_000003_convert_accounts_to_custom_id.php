<?php

use App\Helpers\PkSwapHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! PkSwapHelper::isIntegerColumn('accounts', 'id')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        Schema::table('accounts', function (Blueprint $table) {
            $table->string('custom_id', 20)->nullable()->after('id');
        });

        $this->backfillAccounts();

        Schema::table('accounts', function (Blueprint $table) {
            $table->string('custom_id', 20)->nullable(false)->change();
        });

        $txFks = Schema::getForeignKeys('transactions');
        foreach ($txFks as $fk) {
            $col = $fk['columns'][0] ?? '';
            if (in_array($col, ['account_id', 'transfer_to_account_id']) && ($fk['foreign_table'] ?? '') === 'accounts') {
                Schema::table('transactions', function (Blueprint $t) use ($fk) {
                    $t->dropForeign($fk['name']);
                });
            }
        }

        foreach (['account_id', 'transfer_to_account_id'] as $col) {
            $legacyCol = $col.'_legacy';

            if (Schema::hasColumn('transactions', $legacyCol) && ! Schema::hasColumn('transactions', $col)) {
                Schema::table('transactions', function (Blueprint $table) use ($col, $legacyCol) {
                    $table->renameColumn($legacyCol, $col);
                });
            }

            if (! Schema::hasColumn('transactions', $legacyCol)) {
                Schema::table('transactions', function (Blueprint $table) use ($col, $legacyCol) {
                    $table->renameColumn($col, $legacyCol);
                });

                Schema::table('transactions', function (Blueprint $table) use ($col, $legacyCol) {
                    $table->string($col, 20)->nullable()->after($legacyCol);
                });

                DB::table('transactions')
                    ->join('accounts', "transactions.{$legacyCol}", '=', 'accounts.id')
                    ->update([
                        "transactions.{$col}" => DB::raw('accounts.custom_id'),
                    ]);
            }

            $indexes = Schema::getIndexes('transactions');
            foreach ($indexes as $index) {
                if (in_array($legacyCol, $index['columns'] ?? [], true)) {
                    Schema::table('transactions', function (Blueprint $table) use ($index) {
                        $table->dropIndex($index['name']);
                    });
                }
            }

            if (Schema::hasColumn('transactions', $legacyCol)) {
                Schema::table('transactions', function (Blueprint $table) use ($col, $legacyCol) {
                    $table->string($col, 20)->nullable($col === 'account_id' ? false : true)->change();
                    $table->dropColumn($legacyCol);
                });
            }
        }

        $this->updatePolymorphicIds('App\Models\Account', 'accounts');

        if ($driver === 'sqlite') {
            $this->swapPkSqliteForAccounts();
        } elseif ($driver === 'mysql') {
            Schema::withoutForeignKeyConstraints(function () {
                $this->swapPkMysql('accounts', 'custom_id');
            });
        } elseif ($driver === 'pgsql') {
            $this->swapPkPostgres('accounts', 'custom_id');
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('transfer_to_account_id')->references('id')->on('accounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('This migration is irreversible; restore from backup.');
    }

    private function backfillAccounts(): void
    {
        $accounts = DB::table('accounts')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy(function ($a) {
                return substr($a->created_at, 0, 7);
            });

        foreach ($accounts as $period => $group) {
            $rank = 0;
            $yy = substr($period, 2, 2);
            $mm = substr($period, 5, 2);
            $periodKey = $yy.$mm;

            foreach ($group as $account) {
                $rank++;
                $customId = 'acc-'.$periodKey.str_pad($rank, 3, '0', STR_PAD_LEFT);

                DB::table('accounts')->where('id', $account->id)
                    ->update(['custom_id' => $customId]);
            }

            DB::table('custom_id_counters')->updateOrInsert(
                ['model_type' => 'account', 'period' => $periodKey],
                ['last_number' => $rank, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function updatePolymorphicIds(string $modelType, string $table): void
    {
        DB::table('activities')
            ->join($table, 'activities.subject_id', '=', "{$table}.id")
            ->where('activities.subject_type', $modelType)
            ->update([
                'activities.subject_id' => DB::raw("{$table}.custom_id"),
            ]);

        DB::table('audit_logs')
            ->join($table, 'audit_logs.model_id', '=', "{$table}.id")
            ->where('audit_logs.model_type', $modelType)
            ->update([
                'audit_logs.model_id' => DB::raw("{$table}.custom_id"),
            ]);
    }

    private function swapPkSqliteForAccounts(): void
    {
        Schema::create('accounts_new', function (Blueprint $t) {
            $t->string('id', 20)->primary();
            $t->foreignUuid('user_id');
            $t->foreignId('currency_id');
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('type');
            $t->decimal('balance', 15, 4)->default(0);
            $t->decimal('initial_balance', 15, 4)->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        DB::statement(
            'INSERT INTO accounts_new (id, user_id, currency_id, name, description, type, balance, initial_balance, is_active, created_at, updated_at) '.
            'SELECT custom_id, user_id, currency_id, name, description, type, balance, initial_balance, is_active, created_at, updated_at FROM accounts'
        );

        Schema::drop('accounts');
        Schema::rename('accounts_new', 'accounts');

        Schema::table('accounts', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('restrict');
            $table->unique(['user_id', 'name']);
        });
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
