<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                TextColumn::make('email')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->icon('heroicon-m-envelope'),
                
                TextColumn::make('role')
                    ->label('Role')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'user' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Administrator',
                        'user' => 'User',
                        default => ucfirst($state),
                    }),
                
                IconColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->state(fn ($record) => !is_null($record->email_verified_at)),
                
                IconColumn::make('two_factor_enabled')
                    ->label('2FA')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->state(fn ($record) => !empty($record->two_factor_secret)),
                
                TextColumn::make('accounts_count')
                    ->label('Accounts')
                    ->counts('accounts')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
                
                TextColumn::make('transactions_count')
                    ->label('Transactions')
                    ->counts('transactions')
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),
                
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'user' => 'User',
                        'admin' => 'Administrator',
                    ]),
                
                TernaryFilter::make('email_verified_at')
                    ->label('Email Verification')
                    ->placeholder('All users')
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('email_verified_at'),
                        false: fn ($query) => $query->whereNull('email_verified_at'),
                    ),
                
                TernaryFilter::make('two_factor_enabled')
                    ->label('Two-Factor Authentication')
                    ->placeholder('All users')
                    ->trueLabel('2FA enabled')
                    ->falseLabel('2FA disabled')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('two_factor_secret'),
                        false: fn ($query) => $query->whereNull('two_factor_secret'),
                    ),
                
                TrashedFilter::make()
                    ->label('Deleted Users'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->color('info'),
                EditAction::make()
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning'),
                Action::make('toggle_role')
                    ->label(fn ($record) => $record->role === 'admin' ? 'Remove Admin' : 'Make Admin')
                    ->icon(fn ($record) => $record->role === 'admin' ? 'heroicon-m-shield-exclamation' : 'heroicon-m-shield-check')
                    ->color(fn ($record) => $record->role === 'admin' ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => ($record->role === 'admin' ? 'Remove Admin Role' : 'Grant Admin Role'))
                    ->modalDescription(fn ($record) => 'Are you sure you want to ' . ($record->role === 'admin' ? 'remove admin privileges from' : 'grant admin privileges to') . ' this user? This will affect their access to the admin panel.')
                    ->modalSubmitActionLabel(fn ($record) => $record->role === 'admin' ? 'Remove Admin' : 'Grant Admin')
                    ->action(fn ($record) => $record->update(['role' => $record->role === 'admin' ? 'user' : 'admin']))
                    ->successNotificationTitle(fn ($record) => 'User role updated successfully')
                    ->visible(fn ($record) => (Auth::user()?->isAdmin() ?? false) && $record->id !== Auth::id()),
                Action::make('reset_2fa')
                    ->label('Reset 2FA')
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Two-Factor Authentication')
                    ->modalDescription('Are you sure you want to reset this user\'s two-factor authentication? They will need to set it up again.')
                    ->modalSubmitActionLabel('Reset 2FA')
                    ->action(function ($record) {
                        $record->update([
                            'two_factor_secret' => null,
                            'two_factor_recovery_codes' => null,
                            'two_factor_confirmed_at' => null,
                        ]);
                    })
                    ->successNotificationTitle('Two-factor authentication reset successfully')
                    ->visible(fn ($record) => $record->two_factor_secret !== null),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected users')
                        ->modalDescription('Are you sure you want to delete these users? This will soft delete them and they can be restored later.')
                        ->modalSubmitActionLabel('Yes, delete them')
                        ->successNotificationTitle('Users deleted successfully'),
                    RestoreBulkAction::make()
                        ->successNotificationTitle('Users restored successfully'),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Permanently delete selected users')
                        ->modalDescription('Are you sure you want to permanently delete these users? This action cannot be undone and will remove all their data.')
                        ->modalSubmitActionLabel('Yes, delete permanently')
                        ->successNotificationTitle('Users permanently deleted'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
