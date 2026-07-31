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
        Schema::create('transactions', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('account_id', 20);
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->decimal('amount', 15, 4); // Positive for income, negative for expense
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('category')->nullable();
            $table->string('reference_number')->nullable();
            $table->datetime('transaction_date');
            $table->string('transfer_to_account_id', 20)->nullable();
            $table->foreign('transfer_to_account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->timestamps();
            
            // Index for better performance on tenant queries
            $table->index(['user_id', 'transaction_date']);
            $table->index(['account_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
