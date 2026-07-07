<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Services\RestoreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class RestoreBackupJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public Backup $backup)
    {
    }

    public function handle(RestoreService $service): void
    {
        $service->restore($this->backup);
    }
}
