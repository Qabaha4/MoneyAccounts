<?php

namespace App\Http\Controllers\Admin;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupDownloadController
{
    public function __invoke(Request $request, Backup $backup, BackupService $service): BinaryFileResponse
    {
        if (!$backup->isCompleted()) {
            abort(404, 'Backup is not yet completed.');
        }

        $path = $service->getDownloadPath($backup);

        if (!$path) {
            abort(404, 'Backup file not found on disk.');
        }

        return response()->download($path, $backup->filename);
    }
}
