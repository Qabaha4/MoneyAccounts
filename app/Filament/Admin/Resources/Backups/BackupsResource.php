<?php

namespace App\Filament\Admin\Resources\Backups;

use App\Filament\Admin\Resources\Backups\Pages;
use App\Filament\Admin\Resources\Backups\Tables\BackupsTable;
use App\Models\Backup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class BackupsResource extends Resource
{
    protected static ?string $model = Backup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'System Management';

    protected static ?string $navigationLabel = 'Backups';

    protected static ?string $modelLabel = 'Backup';

    protected static ?string $pluralModelLabel = 'Backups';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return BackupsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBackups::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Backup::where('status', Backup::STATUS_FAILED)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|null
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): string|null
    {
        $count = Backup::where('status', Backup::STATUS_FAILED)->count();
        return $count === 1 ? '1 failed backup' : "{$count} failed backups";
    }
}
