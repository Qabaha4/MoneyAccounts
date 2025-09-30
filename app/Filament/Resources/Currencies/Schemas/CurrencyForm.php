<?php

namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Currency Code')
                            ->required()
                            ->maxLength(3)
                            ->placeholder('USD')
                            ->unique(ignoreRecord: true),

                        TextInput::make('name')
                            ->label('Currency Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('US Dollar'),

                        TextInput::make('symbol')
                            ->label('Currency Symbol')
                            ->required()
                            ->maxLength(10)
                            ->placeholder('$'),

                        TextInput::make('decimal_places')
                            ->label('Decimal Places')
                            ->numeric()
                            ->required()
                            ->default(2)
                            ->minValue(0)
                            ->maxValue(8),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
