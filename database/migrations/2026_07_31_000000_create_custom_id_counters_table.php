<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_id_counters', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('period');
            $table->integer('last_number')->default(0);
            $table->timestamps();

            $table->unique(['model_type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_id_counters');
    }
};
