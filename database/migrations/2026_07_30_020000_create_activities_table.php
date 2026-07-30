<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('action');
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'subject_type', 'action']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
