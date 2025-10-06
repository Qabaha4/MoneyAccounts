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
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Tenant scoping
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->decimal('amount', 15, 4); // Positive for income, negative for expense
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('category')->nullable();
            $table->string('reference_number')->nullable();
            $table->datetime('transaction_date');
            $table->foreignId('transfer_to_account_id')->nullable()->constrained('accounts')->onDelete('set null'); // For transfers
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
