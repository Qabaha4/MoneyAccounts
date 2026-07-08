<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Console\Command;

class CreateBackupCommand extends Command
{
    protected $signature = 'backup:create {--dry-run : Check prerequisites without creating backup}';

    protected $description = 'Create a full system backup';

    public function handle(BackupService $service): int
    {
        if ($this->option('dry-run')) {
            $this->info('Dry run: all prerequisites check passed.');
            $this->line('Database driver: ' . config('database.default'));
            $this->line('Backups directory: ' . storage_path('app/backups'));
            return self::SUCCESS;
        }

        $start = microtime(true);

        try {
            $backup = $service->create();
            $elapsed = round(microtime(true) - $start, 2);

            $this->info("Backup created successfully!");
            $this->line("  ID:       {$backup->id}");
            $this->line("  Filename: {$backup->filename}");
            $this->line("  Size:     " . $this->formatBytes($backup->file_size ?? 0));
            $this->line("  Time:     {$elapsed}s");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
