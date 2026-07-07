<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Services\RestoreService;
use Illuminate\Console\Command;

class RestoreBackupCommand extends Command
{
    protected $signature = 'backup:restore {backup : The ID of the backup to restore} {--force : Skip confirmation prompt}';

    protected $description = 'Restore the system from a backup archive';

    public function handle(RestoreService $service): int
    {
        $backup = Backup::find($this->argument('backup'));

        if (!$backup) {
            $this->error("Backup #{$this->argument('backup')} not found.");
            return self::FAILURE;
        }

        if (!$backup->isCompleted()) {
            $this->error('Cannot restore from an incomplete backup.');
            return self::FAILURE;
        }

        $this->warn('⚠️  RESTORE WILL OVERWRITE ALL CURRENT DATA!');
        $this->line("  Backup: {$backup->filename}");
        $this->line("  Created: {$backup->created_at}");
        $this->line("  Size:    " . $this->formatBytes($backup->file_size ?? 0));
        $this->line('');
        $this->line('A fresh backup will be created automatically before restoring.');

        if (!$this->option('force') && !$this->confirm('Are you sure you want to proceed with the restore?')) {
            $this->info('Restore cancelled.');
            return self::SUCCESS;
        }

        $start = microtime(true);

        try {
            $service->restore($backup);
            $elapsed = round(microtime(true) - $start, 2);

            $this->info('System restored successfully!');
            $this->line("  Backup:    {$backup->filename}");
            $this->line("  Time:      {$elapsed}s");
            $this->line('A safety backup was created before the restore.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Restore failed: {$e->getMessage()}");

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
