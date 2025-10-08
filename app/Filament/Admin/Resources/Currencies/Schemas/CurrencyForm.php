<?php

namespace App\Filament\Admin\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Currency Information')
                    ->description('Basic currency details and configuration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Currency Code')
                                    ->required()
                                    ->maxLength(3)
                                    ->placeholder('USD')
                                    ->helperText('ISO 4217 currency code (3 characters)')
                                    ->rule('regex:/^[A-Z]{3}$/')
                                    ->unique(ignoreRecord: true),
                                
                                TextInput::make('name')
                                    ->label('Currency Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('US Dollar'),
                                
                                TextInput::make('symbol')
                                    ->label('Currency Symbol')
                                    ->required()
                                    ->maxLength(10)
                                    ->placeholder('$'),
                                
                                TextInput::make('decimal_places')
                                    ->label('Decimal Places')
                                    ->required()
                                    ->numeric()
                                    ->default(2)
                                    ->minValue(0)
                                    ->maxValue(8)
                                    ->helperText('Number of decimal places for this currency'),
                            ]),
                        
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Enable this currency for transactions')
                            ->default(true),
                    ]),
                
                Section::make('Exchange Rate Management')
                    ->description('Configure exchange rate settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('exchange_rate')
                                    ->label('Exchange Rate to USD')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->placeholder('1.000000')
                                    ->helperText('Rate to convert 1 unit of this currency to USD'),
                                
                                TextInput::make('rate_source')
                                    ->label('Rate Source')
                                    ->maxLength(100)
                                    ->placeholder('Manual, API, etc.')
                                    ->helperText('Source of the exchange rate'),
                            ]),
                        
                        Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(500)
                            ->placeholder('Additional notes about this currency...')
                            ->rows(3),
                    ]),
            ]);
    }
}
