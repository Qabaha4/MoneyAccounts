<?php

namespace App\Filament\Admin\Resources\Backups\Pages;

use App\Filament\Admin\Resources\Backups\BackupsResource;
use App\Jobs\CreateBackupJob;
use App\Models\Backup;
use App\Services\BackupService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListBackups extends ListRecords
{
    protected static string $resource = BackupsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBackup')
                ->label('Create Backup')
                ->icon('heroicon-m-plus-circle')
                ->color('primary')
                ->action(function () {
                    dispatch(new CreateBackupJob());

                    Notification::make()
                        ->title('Backup creation has been queued.')
                        ->success()
                        ->send();
                }),

            Action::make('uploadBackup')
                ->label('Upload Backup')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('gray')
                ->form([
                    FileUpload::make('file')
                        ->label('Backup Archive (.zip)')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/zip-compressed'])
                        ->disk('backups')
                        ->preserveFileNames()
                        ->maxSize(500 * 1024 * 1024)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $filename = $data['file'];
                    $disk = Storage::disk('backups');

                    Backup::create([
                        'filename' => $filename,
                        'file_path' => $filename,
                        'file_size' => $disk->size($filename),
                        'status' => Backup::STATUS_COMPLETED,
                        'notes' => 'Uploaded from local device',
                        'started_at' => now(),
                        'completed_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Backup uploaded successfully.')
                        ->body($filename)
                        ->success()
                        ->send();
                }),
        ];
    }
}
