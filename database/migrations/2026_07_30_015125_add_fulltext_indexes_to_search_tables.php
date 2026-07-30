<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE accounts ADD FULLTEXT INDEX accounts_search_fulltext (name, description)');
        DB::statement('ALTER TABLE transactions ADD FULLTEXT INDEX transactions_search_fulltext (description, notes, category, reference_number)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex('accounts_search_fulltext');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_search_fulltext');
        });
    }
};
