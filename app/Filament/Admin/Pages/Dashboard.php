<?php

namespace App\Filament\Admin\Pages;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\ChartWidget;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        return [
            AdminStatsWidget::class,
            SystemOverviewWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 2;
    }
}

class AdminStatsWidget extends BaseStatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            BaseStatsOverviewWidget\Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            
            BaseStatsOverviewWidget\Stat::make('Active Currencies', Currency::where('is_active', true)->count())
                ->description('Available currencies')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
            
            BaseStatsOverviewWidget\Stat::make('Total Accounts', Account::count())
                ->description('User accounts')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('warning'),
            
            BaseStatsOverviewWidget\Stat::make('Total Transactions', Transaction::count())
                ->description('All transactions')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('primary'),
        ];
    }
}

class SystemOverviewWidget extends ChartWidget
{
    protected ?string $heading = 'User Registration Trend';
    
    protected function getData(): array
    {
        $users = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $users->pluck('count')->toArray(),
                    'backgroundColor' => '#3B82F6',
                    'borderColor' => '#1E40AF',
                ],
            ],
            'labels' => $users->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}