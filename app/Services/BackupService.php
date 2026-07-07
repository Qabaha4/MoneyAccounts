<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class BackupService
{
    protected string $backupsDisk;

    protected int $minDiskSpaceBytes = 500 * 1024 * 1024;

    public function __construct()
    {
        $this->backupsDisk = 'backups';
    }

    public function create(): Backup
    {
        $this->checkDiskSpace();

        $timestamp = now()->format('Ymd-His');
        $random = Str::random(4);
        $filename = "moneyaccounts-backup-{$timestamp}-{$random}.zip";
        $tempDir = Storage::disk($this->backupsDisk)->path("tmp_{$timestamp}_{$random}");

        $backup = Backup::create([
            'filename' => $filename,
            'file_path' => $filename,
            'status' => Backup::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        try {
            $this->ensureDirectoryExists($tempDir);

            $this->dumpDatabase($tempDir);

            $this->copyUploadedFiles($tempDir);

            $zipPath = $this->createZipArchive($tempDir, $filename);

            $this->cleanupTempDir($tempDir);

            $backup->update([
                'file_size' => Storage::disk($this->backupsDisk)->size($filename),
                'status' => Backup::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $this->cleanupTempDir($tempDir ?? null);
            $this->cleanupFailedZip($filename ?? null);

            $backup->update([
                'status' => Backup::STATUS_FAILED,
                'notes' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }

        return $backup->fresh();
    }

    public function upload(UploadedFile $file): Backup
    {
        $allowedMimes = ['application/zip', 'application/x-zip-compressed', 'application/zip-compressed'];

        if ($file->getClientOriginalExtension() !== 'zip' || !in_array($file->getMimeType(), $allowedMimes)) {
            throw ValidationException::withMessages(['file' => 'Only ZIP files are accepted.']);
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $timestamp = now()->format('Ymd-His');
        $random = Str::random(4);
        $filename = "{$originalName}-{$timestamp}-{$random}.zip";
        $disk = Storage::disk($this->backupsDisk);

        $disk->putFileAs('/', $file, $filename);

        $backup = Backup::create([
            'filename' => $filename,
            'file_path' => $filename,
            'file_size' => $disk->size($filename),
            'status' => Backup::STATUS_COMPLETED,
            'notes' => 'Uploaded from local device',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        return $backup;
    }

    public function listAll(int $perPage = 20): mixed
    {
        return Backup::orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?Backup
    {
        return Backup::find($id);
    }

    public function delete(Backup $backup): void
    {
        if ($backup->isProcessing()) {
            throw new \RuntimeException('Cannot delete a backup that is currently being generated.');
        }

        $disk = Storage::disk($this->backupsDisk);
        if ($disk->exists($backup->file_path)) {
            $disk->delete($backup->file_path);
        }

        $backup->delete();
    }

    public function getDownloadPath(Backup $backup): ?string
    {
        if (!$backup->isCompleted()) {
            return null;
        }

        $disk = Storage::disk($this->backupsDisk);
        if ($disk->exists($backup->file_path)) {
            return $disk->path($backup->file_path);
        }

        return null;
    }

    protected function checkDiskSpace(): void
    {
        $freeSpace = disk_free_space(Storage::disk($this->backupsDisk)->path('/'));
        if ($freeSpace !== false && $freeSpace < $this->minDiskSpaceBytes) {
            throw new \RuntimeException(
                'Insufficient disk space. At least ' . $this->formatBytes($this->minDiskSpaceBytes) . ' required for backup generation.'
            );
        }
    }

    protected function dumpDatabase(string $tempDir): void
    {
        $jsonPath = "{$tempDir}/database.json";

        $tableNames = $this->getTableNames();

        $data = [
            'version' => 1,
            'driver' => DB::connection()->getDriverName(),
            'created_at' => now()->toIso8601String(),
            'tables' => [],
        ];

        foreach ($tableNames as $tableName) {
            $data['tables'][$tableName] = DB::table($tableName)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->toArray();
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if (file_put_contents($jsonPath, $json) === false) {
            throw new \RuntimeException("Failed to write database backup to: {$jsonPath}");
        }
    }

    protected function getTableNames(): array
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => array_map(
                fn ($t) => $t->name,
                DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
            ),
            'mysql' => (function () {
                $key = 'Tables_in_' . DB::connection()->getDatabaseName();
                return array_map(fn ($t) => $t->$key, DB::select('SHOW TABLES'));
            })(),
            'pgsql' => array_map(
                fn ($t) => $t->tablename,
                DB::select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'")
            ),
            default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
        };
    }

    protected function copyUploadedFiles(string $tempDir): void
    {
        $publicPath = storage_path('app/public');
        $destPath = "{$tempDir}/storage/public";

        if (is_dir($publicPath)) {
            $this->copyRecursive($publicPath, $destPath);
        }
    }

    protected function createZipArchive(string $sourceDir, string $filename): string
    {
        $zipPath = Storage::disk($this->backupsDisk)->path($filename);
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Failed to create ZIP archive at: {$zipPath}");
        }

        $baseDirName = 'moneyaccounts-backup-' . now()->format('Ymd-His');

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $relativePath = $baseDirName . '/' . substr($file->getRealPath(), strlen($sourceDir) + 1);
            $zip->addFile($file->getRealPath(), $relativePath);
        }

        $zip->close();

        return $zipPath;
    }

    protected function cleanupTempDir(?string $tempDir): void
    {
        if ($tempDir && is_dir($tempDir)) {
            $this->removeDirectory($tempDir);
        }
    }

    protected function cleanupFailedZip(?string $filename): void
    {
        if ($filename) {
            $disk = Storage::disk($this->backupsDisk);
            if ($disk->exists($filename)) {
                $disk->delete($filename);
            }
        }
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
