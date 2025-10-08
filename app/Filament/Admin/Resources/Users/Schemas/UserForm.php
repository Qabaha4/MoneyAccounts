<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->description('Basic user account details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('John Doe'),
                                
                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('john@example.com'),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->minLength(8)
                                    ->placeholder('Enter secure password'),
                                
                                DateTimePicker::make('email_verified_at')
                                    ->label('Email Verified At')
                                    ->placeholder('Not verified')
                                    ->helperText('When the user verified their email'),
                            ]),
                    ]),
                
                Section::make('Role & Permissions')
                    ->description('User role and access control')
                    ->schema([
                        Select::make('role')
                            ->label('User Role')
                            ->required()
                            ->options([
                                'user' => 'User',
                                'admin' => 'Administrator',
                            ])
                            ->default('user')
                            ->disabled(function () {
                                $currentUserId = auth()->id();
                                $editingUserId = request()->route('record');
                                return $currentUserId && $editingUserId && (int)$editingUserId === (int)$currentUserId;
                            })
                            ->helperText(function () {
                                $currentUserId = auth()->id();
                                $editingUserId = request()->route('record');
                                $isEditingSelf = $currentUserId && $editingUserId && (int)$editingUserId === (int)$currentUserId;
                                return $isEditingSelf 
                                    ? 'You cannot change your own role to prevent losing admin access'
                                    : 'Administrators have full access to the admin panel';
                            }),
                    ]),
                
                Section::make('Two-Factor Authentication')
                    ->description('Two-factor authentication settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('has_2fa')
                                    ->label('2FA Enabled')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($record) => $record ? !empty($record->two_factor_secret) : false)
                                    ->helperText('Indicates if 2FA is currently enabled'),
                                
                                DateTimePicker::make('two_factor_confirmed_at')
                                    ->label('2FA Confirmed At')
                                    ->placeholder('Not confirmed')
                                    ->helperText('When 2FA was last confirmed'),
                            ]),
                        
                        Textarea::make('two_factor_secret')
                            ->label('2FA Secret')
                            ->placeholder('Encrypted 2FA secret')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Encrypted two-factor authentication secret')
                            ->columnSpanFull(),
                        
                        Textarea::make('two_factor_recovery_codes')
                            ->label('Recovery Codes')
                            ->placeholder('Encrypted recovery codes')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Encrypted backup recovery codes')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
