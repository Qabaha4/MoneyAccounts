<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use App\Models\Account;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('account_id')
                            ->required()
                            ->relationship('account', 'name')
                            ->options(Account::active()->pluck('name', 'id'))
                            ->searchable()
                            ->label('Account'),
                        
                        Select::make('type')
                            ->required()
                            ->options([
                                'income' => 'Income',
                                'expense' => 'Expense',
                                'transfer' => 'Transfer',
                            ])
                            ->label('Transaction Type'),
                        
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->label('Amount'),
                        
                        DatePicker::make('transaction_date')
                            ->required()
                            ->default(now())
                            ->label('Transaction Date'),
                        
                        Select::make('transfer_to_account_id')
                            ->relationship('transferToAccount', 'name')
                            ->options(Account::active()->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => $get('type') === 'transfer')
                            ->label('Transfer To Account'),
                    ]),
                
                Textarea::make('description')
                    ->maxLength(500)
                    ->label('Description'),
            ]);
    }
}
