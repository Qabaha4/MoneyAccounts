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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Tenant scoping
            $table->foreignId('currency_id')->constrained()->onDelete('restrict');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['checking', 'savings', 'credit', 'investment', 'cash', 'other']);
            $table->decimal('balance', 15, 4)->default(0); // Support up to 4 decimal places
            $table->decimal('initial_balance', 15, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Ensure account names are unique per user (tenant)
            $table->unique(['user_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
