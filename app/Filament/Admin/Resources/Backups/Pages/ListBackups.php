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
                    $queue = config('queue.default');

                    if ($queue === 'sync') {
                        set_time_limit(0);

                        try {
                            app(BackupService::class)->create();

                            Notification::make()
                                ->title('Backup created successfully.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Backup creation failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }

                        return;
                    }

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
                        ->maxSize(500 * 1024 * 1024)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $filename = $data['file'];
                    $disk = Storage::disk('backups');

                    if (!$disk->exists($filename)) {
                        Notification::make()
                            ->title('Upload failed')
                            ->body('File not found after upload.')
                            ->danger()
                            ->persistent()
                            ->send();
                        return;
                    }

                    $mimeType = $disk->mimeType($filename);
                    $allowedMimes = ['application/zip', 'application/x-zip-compressed', 'application/zip-compressed'];

                    if (!in_array($mimeType, $allowedMimes)) {
                        $disk->delete($filename);
                        Notification::make()
                            ->title('Upload failed')
                            ->body('Only ZIP files are accepted.')
                            ->danger()
                            ->persistent()
                            ->send();
                        return;
                    }

                    $backup = Backup::create([
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
                        ->body($backup->filename)
                        ->success()
                        ->send();
                }),
        ];
    }
}
