<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->decimal('exchange_rate', 15, 6)->nullable()->after('decimal_places');
            $table->string('rate_source', 100)->nullable()->after('exchange_rate');
            $table->text('notes')->nullable()->after('rate_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'rate_source', 'notes']);
        });
    }
};
