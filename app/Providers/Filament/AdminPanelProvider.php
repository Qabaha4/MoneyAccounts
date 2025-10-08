<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AdminOnly;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('MoneyAccounts Admin')
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => Color::Amber,
                // 'danger' => Color::Red,
                // 'gray' => Color::Slate,
                // 'info' => Color::Blue,
                // 'success' => Color::Emerald,
                // 'warning' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                AdminOnly::class,
            ])
            ->authGuard('web')
            // ->login()
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label('Profile')
                    ->url(fn(): string => route('filament.admin.auth.profile'))
                    ->icon('heroicon-m-user-circle'),
                'logout' => \Filament\Navigation\MenuItem::make()
                    ->label('Logout')
                    ->url(fn(): string => route('filament.admin.auth.logout'))
                    ->icon('heroicon-m-arrow-left-on-rectangle'),
            ])
            // ->navigationGroups([
            //     'System Management' => \Filament\Navigation\NavigationGroup::make()
            //         ->label('System Management')
            //         ->icon('heroicon-o-cog-6-tooth')
            //         ->collapsed(),
            //     'Financial Management' => \Filament\Navigation\NavigationGroup::make()
            //         ->label('Financial Management')
            //         ->icon('heroicon-o-currency-dollar')
            //         ->collapsed(),
            // ])
            // ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->spa();
    }
}
