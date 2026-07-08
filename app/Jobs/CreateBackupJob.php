<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(BackupService $service): void
    {
        try {
            $service->create();
        } catch (\Throwable $e) {
            report($e);
            throw $e;
        }
    }
}
