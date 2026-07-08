<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class RestoreService
{
    protected string $backupsDisk;

    protected int $maxExtractFiles = 1000;

    protected int $maxExtractSizeBytes = 500 * 1024 * 1024;

    public function __construct(protected BackupService $backupService)
    {
        $this->backupsDisk = 'backups';
    }

    public function restore(Backup $backup): void
    {
        if (!$backup->isCompleted()) {
            throw new \RuntimeException('Cannot restore from an incomplete backup.');
        }

        $zipPath = $this->backupService->getDownloadPath($backup);
        if (!$zipPath) {
            throw new \RuntimeException('Backup file not found on disk.');
        }

        // Create safety backup and capture its data before database is replaced
        $safetyBackup = $this->backupService->create();
        $safetyBackupData = $safetyBackup->only([
            'filename', 'file_path', 'file_size', 'status', 'notes',
            'started_at', 'completed_at', 'created_at', 'updated_at',
        ]);

        $tempDir = storage_path('app/backups/restore_' . Str::random(8));
        $this->ensureDirectoryExists($tempDir);

        try {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \RuntimeException('Failed to open backup archive.');
            }

            $this->validateZipArchive($zip);

            $zip->extractTo($tempDir);
            $zip->close();

            $items = array_diff(scandir($tempDir), ['.', '..']);
            $baseDir = null;
            foreach ($items as $item) {
                if (is_dir("{$tempDir}/{$item}") && str_starts_with($item, 'moneyaccounts-backup')) {
                    $baseDir = "{$tempDir}/{$item}";
                    break;
                }
            }

            if (!$baseDir) {
                throw new \RuntimeException('Invalid backup archive: no backup directory found.');
            }

            $this->restoreDatabase("{$baseDir}/database.json");

            // Re-create safety backup record in the restored database
            DB::table('backups')->insert($safetyBackupData);

            $this->restoreFiles("{$baseDir}/storage/public");

            $this->removeDirectory($tempDir);
        } catch (\Throwable $e) {
            $this->removeDirectory($tempDir);
            throw $e;
        }
    }

    protected function validateZipArchive(ZipArchive $zip): void
    {
        $fileCount = $zip->numFiles;
        if ($fileCount === false || $fileCount > $this->maxExtractFiles) {
            throw new \RuntimeException('Backup archive contains too many files (' . ($fileCount ?: 0) . '). Maximum allowed: ' . $this->maxExtractFiles);
        }

        $totalSize = 0;
        for ($i = 0; $i < $fileCount; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                throw new \RuntimeException('Failed to read archive entry at index ' . $i);
            }
            $totalSize += $stat['size'];
            if ($totalSize > $this->maxExtractSizeBytes) {
                throw new \RuntimeException('Backup archive uncompressed size exceeds maximum allowed (' . number_format($this->maxExtractSizeBytes / 1024 / 1024) . ' MB).');
            }
        }
    }

    protected function restoreDatabase(string $jsonPath): void
    {
        if (!file_exists($jsonPath)) {
            throw new \RuntimeException('Database backup file not found in archive.');
        }

        $json = file_get_contents($jsonPath);
        if ($json === false || trim($json) === '') {
            throw new \RuntimeException('Database backup file is empty.');
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse database backup: ' . json_last_error_msg());
        }

        $tables = $data['tables'] ?? [];
        if (empty($tables)) {
            throw new \RuntimeException('Database backup contains no table data.');
        }

        $allowedTables = $this->backupService->getTableNames();
        foreach (array_keys($tables) as $tableName) {
            if (!in_array($tableName, $allowedTables, true)) {
                throw new \RuntimeException("Invalid table '{$tableName}' found in backup. Table does not exist in the current database schema.");
            }
        }

        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'sqlite' => DB::statement('PRAGMA foreign_keys = OFF'),
            'mysql' => DB::statement('SET FOREIGN_KEY_CHECKS = 0'),
            'pgsql' => DB::statement('SET session_replication_role = replica'),
            default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
        };

        try {
            foreach ($tables as $tableName => $rows) {
                DB::table($tableName)->truncate();

                if (!empty($rows)) {
                    foreach (array_chunk($rows, 100) as $chunk) {
                        DB::table($tableName)->insert($chunk);
                    }
                }
            }
        } finally {
            match ($driver) {
                'sqlite' => DB::statement('PRAGMA foreign_keys = ON'),
                'mysql' => DB::statement('SET FOREIGN_KEY_CHECKS = 1'),
                'pgsql' => DB::statement('SET session_replication_role = DEFAULT'),
                default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
            };
        }
    }

    protected function restoreFiles(string $sourcePath): void
    {
        if (!is_dir($sourcePath)) {
            return;
        }

        $destPath = storage_path('app/public');
        $this->ensureDirectoryExists($destPath);

        $this->copyRecursive($sourcePath, $destPath);
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    protected function copyRecursive(string $source, string $dest): void
    {
        $this->ensureDirectoryExists($dest);
        $dir = dir($source);
        while (($entry = $dir->read()) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $srcPath = "{$source}/{$entry}";
            $dstPath = "{$dest}/{$entry}";
            if (is_dir($srcPath)) {
                $this->copyRecursive($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
        $dir->close();
    }

    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
