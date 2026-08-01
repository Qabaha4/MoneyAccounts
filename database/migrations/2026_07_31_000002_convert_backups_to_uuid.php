<?php

use App\Helpers\PkSwapHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!PkSwapHelper::isIntegerColumn('backups', 'id')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        Schema::table('backups', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('backups')->whereNull('uuid')
            ->orderBy('created_at')->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('backups')->where('id', $row->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });

        Schema::table('backups', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        if ($driver === 'sqlite') {
            Schema::create('backups_new', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->string('filename', 255);
                $t->string('file_path', 500);
                $t->unsignedBigInteger('file_size')->nullable();
                $t->string('status', 20)->default('pending');
                $t->text('notes')->nullable();
                $t->dateTime('started_at')->nullable();
                $t->dateTime('completed_at')->nullable();
                $t->timestamps();
            });

            DB::statement(
                'INSERT INTO backups_new (id, filename, file_path, file_size, status, notes, started_at, completed_at, created_at, updated_at) ' .
                'SELECT uuid, filename, file_path, file_size, status, notes, started_at, completed_at, created_at, updated_at FROM backups'
            );

            Schema::drop('backups');
            Schema::rename('backups_new', 'backups');

            Schema::table('backups', function (Blueprint $table) {
                $table->index('status', 'idx_backups_status');
                $table->index('created_at', 'idx_backups_created_at');
            });
        } elseif ($driver === 'mysql') {
            Schema::withoutForeignKeyConstraints(function () {
                DB::statement('ALTER TABLE backups MODIFY id BIGINT UNSIGNED NOT NULL');
                Schema::table('backups', fn (Blueprint $t) => $t->dropPrimary());

                Schema::table('backups', function (Blueprint $t) {
                    $t->renameColumn('id', 'legacy_id');
                    $t->renameColumn('uuid', 'id');
                });

                Schema::table('backups', fn (Blueprint $t) => $t->primary('id'));
                Schema::table('backups', fn (Blueprint $t) => $t->dropColumn('legacy_id'));
            });
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE backups ALTER COLUMN id DROP DEFAULT');
            DB::statement('DROP SEQUENCE IF EXISTS backups_id_seq');
            Schema::table('backups', fn (Blueprint $t) => $t->dropPrimary());

            Schema::table('backups', function (Blueprint $t) {
                $t->renameColumn('id', 'legacy_id');
                $t->renameColumn('uuid', 'id');
            });

            Schema::table('backups', fn (Blueprint $t) => $t->primary('id'));
            Schema::table('backups', fn (Blueprint $t) => $t->dropColumn('legacy_id'));
        }
    }

    public function down(): void
    {
        throw new \RuntimeException('This migration is irreversible; restore from backup.');
    }
};
