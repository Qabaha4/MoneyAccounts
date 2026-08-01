<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('passcode_hash')->nullable()->after('password');
            $table->boolean('hide_dashboard_balance')->default(false)->after('passcode_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['passcode_hash', 'hide_dashboard_balance']);
        });
    }
};
