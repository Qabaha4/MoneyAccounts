<?php

namespace App\Filament\Resources\Accounts\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use App\Models\Currency;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Account Name'),
                        
                        Select::make('currency_id')
                            ->required()
                            ->relationship('currency', 'name')
                            ->options(Currency::active()->pluck('name', 'id'))
                            ->searchable()
                            ->label('Currency'),
                        
                        Select::make('type')
                            ->required()
                            ->options([
                                'checking' => 'Checking',
                                'savings' => 'Savings',
                                'credit' => 'Credit',
                                'investment' => 'Investment',
                                'cash' => 'Cash',
                                'other' => 'Other',
                            ])
                            ->default('checking')
                            ->label('Account Type'),
                        
                        TextInput::make('initial_balance')
                            ->numeric()
                            ->default(0)
                            ->step(0.01)
                            ->label('Initial Balance'),
                        
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ]),
            ]);
    }
}
