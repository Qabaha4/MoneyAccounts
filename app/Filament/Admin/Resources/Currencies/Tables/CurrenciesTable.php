<?php

namespace App\Filament\Admin\Resources\Currencies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CurrenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Currency code copied!')
                    ->badge()
                    ->color('primary'),
                
                TextColumn::make('name')
                    ->label('Currency Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->symbol),
                
                TextColumn::make('symbol')
                    ->label('Symbol')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                TextColumn::make('decimal_places')
                    ->label('Decimals')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                
                TextColumn::make('exchange_rate')
                    ->label('Exchange Rate')
                    ->numeric(decimalPlaces: 6)
                    ->sortable()
                    ->placeholder('Not set')
                    ->description('Rate to USD'),
                
                TextColumn::make('rate_source')
                    ->label('Rate Source')
                    ->placeholder('Manual')
                    ->badge()
                    ->color('info'),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All currencies')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                
                SelectFilter::make('decimal_places')
                    ->label('Decimal Places')
                    ->options([
                        0 => '0 decimals',
                        2 => '2 decimals',
                        4 => '4 decimals',
                        6 => '6 decimals',
                        8 => '8 decimals',
                    ]),
                
                TrashedFilter::make()
                    ->label('Deleted Records'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-m-eye'),
                EditAction::make()
                    ->icon('heroicon-m-pencil-square'),
                Action::make('toggle_status')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => ($record->is_active ? 'Deactivate' : 'Activate') . ' Currency')
                    ->modalDescription(fn ($record) => 'Are you sure you want to ' . ($record->is_active ? 'deactivate' : 'activate') . ' this currency? This will affect all related accounts and transactions.')
                    ->modalSubmitActionLabel(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active]))
                    ->successNotificationTitle(fn ($record) => 'Currency ' . ($record->is_active ? 'activated' : 'deactivated') . ' successfully'),
                Action::make('update_exchange_rate')
                    ->label('Update Rate')
                    ->icon('heroicon-m-arrow-path')
                    ->color('info')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('exchange_rate_usd')
                            ->label('Exchange Rate (to USD)')
                            ->numeric()
                            ->step(0.000001)
                            ->minValue(0)
                            ->required()
                            ->helperText('Enter the current exchange rate to USD'),
                        \Filament\Forms\Components\Textarea::make('rate_notes')
                            ->label('Rate Notes')
                            ->placeholder('Optional notes about this rate update')
                            ->maxLength(500),
                    ])
                    ->fillForm(fn ($record) => [
                        'exchange_rate_usd' => $record->exchange_rate_usd,
                        'rate_notes' => $record->rate_notes,
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'exchange_rate_usd' => $data['exchange_rate_usd'],
                            'rate_notes' => $data['rate_notes'] ?? null,
                            'rate_source' => 'Manual Update',
                        ]);
                    })
                    ->successNotificationTitle('Exchange rate updated successfully')
                    ->visible(fn ($record) => $record->code !== 'USD'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected currencies')
                        ->modalDescription('Are you sure you want to delete these currencies? This action cannot be undone and may affect related accounts.')
                        ->modalSubmitActionLabel('Yes, delete them')
                        ->successNotificationTitle('Currencies deleted successfully'),
                    RestoreBulkAction::make()
                        ->successNotificationTitle('Currencies restored successfully'),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Permanently delete selected currencies')
                        ->modalDescription('Are you sure you want to permanently delete these currencies? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete permanently')
                        ->successNotificationTitle('Currencies permanently deleted'),
                ]),
            ])
            ->defaultSort('code', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
