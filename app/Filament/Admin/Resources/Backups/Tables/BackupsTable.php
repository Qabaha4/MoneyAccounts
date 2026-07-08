<?php

namespace App\Filament\Admin\Resources\Backups\Tables;

use App\Models\Backup;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BackupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('filename')
                    ->label('Filename')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-archive-box'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => Backup::STATUS_COMPLETED,
                        'warning' => Backup::STATUS_PROCESSING,
                        'danger' => Backup::STATUS_FAILED,
                        'gray' => Backup::STATUS_PENDING,
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => Backup::STATUS_COMPLETED,
                        'heroicon-m-arrow-path' => Backup::STATUS_PROCESSING,
                        'heroicon-m-x-circle' => Backup::STATUS_FAILED,
                        'heroicon-m-clock' => Backup::STATUS_PENDING,
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 2) . ' KB' : '-')
                    ->sortable()
                    ->tooltip(fn (?int $state): ?string => $state ? number_format($state) . ' bytes' : null),

                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->started_at?->format('Y-m-d H:i:s')),

                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->completed_at?->format('Y-m-d H:i:s')),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),

                IconColumn::make('notes')
                    ->label('Notes')
                    ->boolean()
                    ->trueIcon('heroicon-m-exclamation-triangle')
                    ->falseIcon('heroicon-m-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn ($record) => $record->notes),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Backup $record): bool => $record->isCompleted())
                    ->url(fn (Backup $record): string => route('admin.backups.download', $record))
                    ->openUrlInNewTab(),

                Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-m-arrow-path-rounded-square')
                    ->color('warning')
                    ->visible(fn (Backup $record): bool => $record->isCompleted())
                    ->requiresConfirmation()
                    ->modalHeading('Restore from backup?')
                    ->modalDescription('This will OVERWRITE all current data with the data from this backup. A fresh safety backup will be created automatically before the restore. This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, restore')
                    ->action(function (Backup $record) {
                        set_time_limit(0);

                        try {
                            app(\App\Services\RestoreService::class)->restore($record);

                            Notification::make()
                                ->title('System restored successfully!')
                                ->body('A safety backup was created before the restore.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Restore failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Backup $record): bool => !$record->isProcessing())
                    ->action(fn (Backup $record) => app(\App\Services\BackupService::class)->delete($record)),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No backups yet')
            ->emptyStateDescription('Create your first backup to protect your data.')
            ->emptyStateIcon('heroicon-o-archive-box');
    }
}
