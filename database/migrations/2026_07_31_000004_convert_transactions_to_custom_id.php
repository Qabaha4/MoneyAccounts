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
        if (!PkSwapHelper::isIntegerColumn('transactions', 'id')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('custom_id', 20)->nullable()->after('id');
        });

        $this->backfillTransactions();

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('custom_id', 20)->nullable(false)->change();
        });

        $this->updatePolymorphicIds('App\Models\Transaction', 'transactions');

        if ($driver === 'sqlite') {
            $this->swapPkSqliteForTransactions();
        } elseif ($driver === 'mysql') {
            Schema::withoutForeignKeyConstraints(function () {
                $this->swapPkMysql('transactions', 'custom_id');
            });
        } elseif ($driver === 'pgsql') {
            $this->swapPkPostgres('transactions', 'custom_id');
        }
    }

    public function down(): void
    {
        throw new \RuntimeException('This migration is irreversible; restore from backup.');
    }

    private function backfillTransactions(): void
    {
        $transactions = DB::table('transactions')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy(function ($t) {
                return substr($t->created_at, 0, 10);
            });

        foreach ($transactions as $date => $group) {
            $rank = 0;
            $yy = substr($date, 2, 2);
            $mm = substr($date, 5, 2);
            $dd = substr($date, 8, 2);
            $periodKey = $yy . $mm . $dd;

            foreach ($group as $transaction) {
                $rank++;
                $customId = 'trn-' . $periodKey . str_pad($rank, 3, '0', STR_PAD_LEFT);

                DB::table('transactions')->where('id', $transaction->id)
                    ->update(['custom_id' => $customId]);
            }

            DB::table('custom_id_counters')->updateOrInsert(
                ['model_type' => 'transaction', 'period' => $periodKey],
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

    private function swapPkSqliteForTransactions(): void
    {
        Schema::create('transactions_new', function (Blueprint $t) {
            $t->string('id', 20)->primary();
            $t->foreignUuid('user_id');
            $t->string('account_id', 20);
            $t->string('type');
            $t->decimal('amount', 15, 4);
            $t->string('description')->nullable();
            $t->text('notes')->nullable();
            $t->string('category')->nullable();
            $t->string('reference_number')->nullable();
            $t->datetime('transaction_date');
            $t->string('transfer_to_account_id', 20)->nullable();
            $t->decimal('exchange_rate', 15, 6)->nullable();
            $t->decimal('converted_amount', 15, 4)->nullable();
            $t->string('exchange_rate_source')->nullable();
            $t->timestamps();
        });

        DB::statement(
            'INSERT INTO transactions_new (id, user_id, account_id, type, amount, description, notes, ' .
            'category, reference_number, transaction_date, transfer_to_account_id, exchange_rate, ' .
            'converted_amount, exchange_rate_source, created_at, updated_at) ' .
            'SELECT custom_id, user_id, account_id, type, amount, description, notes, ' .
            'category, reference_number, transaction_date, transfer_to_account_id, exchange_rate, ' .
            'converted_amount, exchange_rate_source, created_at, updated_at FROM transactions'
        );

        Schema::drop('transactions');
        Schema::rename('transactions_new', 'transactions');

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('transfer_to_account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->index(['user_id', 'transaction_date']);
            $table->index(['account_id', 'transaction_date']);
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
