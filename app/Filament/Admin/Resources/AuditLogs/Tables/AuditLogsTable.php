<?php

namespace App\Filament\Admin\Resources\AuditLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->description(fn ($record) => $record->user?->email),
                
                BadgeColumn::make('action')
                    ->label('Action')
                    ->colors([
                        'success' => ['created', 'restored'],
                        'warning' => ['updated'],
                        'danger' => ['deleted', 'force_deleted'],
                        'info' => ['viewed'],
                    ])
                    ->icons([
                        'heroicon-m-plus' => 'created',
                        'heroicon-m-pencil' => 'updated',
                        'heroicon-m-trash' => 'deleted',
                        'heroicon-m-arrow-path' => 'restored',
                        'heroicon-m-x-mark' => 'force_deleted',
                        'heroicon-m-eye' => 'viewed',
                    ]),
                
                TextColumn::make('model_type')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->color('gray'),
                
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                        'force_deleted' => 'Force Deleted',
                    ]),
                
                SelectFilter::make('model_type')
                    ->label('Model Type')
                    ->options([
                        'App\\Models\\User' => 'User',
                        'App\\Models\\Currency' => 'Currency',
                        'App\\Models\\Account' => 'Account',
                        'App\\Models\\Transaction' => 'Transaction',
                    ]),
                
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View Details')
                    ->icon('heroicon-m-eye')
                    ->modal()
                    ->form([
                        Section::make('Audit Details')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Placeholder::make('user_name')
                                            ->label('User')
                                            ->content(fn ($record) => $record->user?->name ?? 'System'),
                                        
                                        Placeholder::make('action')
                                            ->label('Action')
                                            ->content(fn ($record) => ucfirst($record->action)),
                                        
                                        Placeholder::make('model_type')
                                            ->label('Model Type')
                                            ->content(fn ($record) => class_basename($record->model_type)),
                                        
                                        Placeholder::make('model_id')
                                            ->label('Model ID')
                                            ->content(fn ($record) => $record->model_id),
                                        
                                        Placeholder::make('ip_address')
                                            ->label('IP Address')
                                            ->content(fn ($record) => $record->ip_address),
                                        
                                        Placeholder::make('created_at')
                                            ->label('Date & Time')
                                            ->content(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),
                                    ]),
                                
                                Placeholder::make('description')
                                    ->label('Description')
                                    ->content(fn ($record) => $record->description)
                                    ->columnSpanFull(),
                                
                                Placeholder::make('user_agent')
                                    ->label('User Agent')
                                    ->content(fn ($record) => $record->user_agent)
                                    ->columnSpanFull(),
                            ]),
                        
                        Section::make('Changes')
                            ->schema([
                                Placeholder::make('old_values')
                                    ->label('Previous Values')
                                    ->content(fn ($record) => $record->old_values ? json_encode($record->old_values, JSON_PRETTY_PRINT) : 'No previous values')
                                    ->visible(fn ($record) => !empty($record->old_values)),
                                
                                Placeholder::make('new_values')
                                    ->label('New Values')
                                    ->content(fn ($record) => $record->new_values ? json_encode($record->new_values, JSON_PRETTY_PRINT) : 'No new values')
                                    ->visible(fn ($record) => !empty($record->new_values)),
                            ])
                            ->visible(fn ($record) => !empty($record->old_values) || !empty($record->new_values))
                            ->collapsible(),
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}
