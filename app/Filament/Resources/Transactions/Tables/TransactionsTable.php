<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                
                TextColumn::make('account.name')
                    ->searchable()
                    ->sortable()
                    ->label('Account'),
                
                BadgeColumn::make('type')
                    ->colors([
                        'success' => 'income',
                        'danger' => 'expense',
                        'warning' => 'transfer',
                    ])
                    ->label('Type'),
                
                TextColumn::make('amount')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->label('Amount'),
                
                TextColumn::make('transferToAccount.name')
                    ->label('Transfer To')
                    ->placeholder('N/A'),
                
                TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->label('Description'),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Created'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'income' => 'Income',
                        'expense' => 'Expense',
                        'transfer' => 'Transfer',
                    ])
                    ->label('Transaction Type'),
                
                SelectFilter::make('account_id')
                    ->relationship('account', 'name')
                    ->label('Account'),
                
                Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($query, $date) => $query->whereDate('transaction_date', '>=', $date))
                            ->when($data['until'], fn ($query, $date) => $query->whereDate('transaction_date', '<=', $date));
                    })
                    ->label('Date Range'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc');
    }
}
