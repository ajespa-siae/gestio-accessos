<?php

namespace App\Providers\Filament;

use App\Http\Middleware\CheckOperatiuRole;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class OperatiuPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('operatiu')
            ->path('operatiu')
            ->login(\App\Filament\Operatiu\Pages\Auth\Login::class)
            ->authGuard('web') // Usar la misma autenticación LDAP
            ->brandName('Gestor d\'Accessos')
            ->colors([
                'primary' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Operatiu/Resources'), for: 'App\\Filament\\Operatiu\\Resources')
            ->discoverPages(in: app_path('Filament/Operatiu/Pages'), for: 'App\\Filament\\Operatiu\\Pages')
            ->discoverWidgets(in: app_path('Filament/Operatiu/Widgets'), for: 'App\\Filament\\Operatiu\\Widgets')
            ->widgets([
                \App\Filament\Operatiu\Widgets\OperatiuDashboard::class,
                \App\Filament\Operatiu\Widgets\UltimesSolicitudsTable::class,
                \App\Filament\Operatiu\Widgets\TasquesPendentsTable::class,
                \App\Filament\Operatiu\Widgets\ValidacionsPendentsTable::class,
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
                CheckOperatiuRole::class, // Middleware personalizado para control de acceso
            ])
            ->navigationGroups([
                NavigationGroup::make('Gestió RRHH'),
                NavigationGroup::make('Tasques IT'),
                NavigationGroup::make('Sol·licituds Accés'),
                NavigationGroup::make('Validacions'),
                NavigationGroup::make('Sistema'),
            ])
            ->userMenuItems([
                'admin_panel' => \Filament\Navigation\MenuItem::make()
                    ->label('Panel Administració')
                    ->url('/admin')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->visible(fn (): bool => auth()->user()->rol_principal === 'admin' || 
                        auth()->user()->hasRole('admin'))
                    ->openUrlInNewTab(),
            ])
            ->navigationItems([
                NavigationItem::make('Panel Admin')
                    ->url('/admin')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->group('Sistema')
                    ->sort(99)
                    ->visible(fn (): bool => auth()->user()->rol_principal === 'admin' || 
                        auth()->user()->hasRole('admin'))
                    ->openUrlInNewTab(),
            ])
            ->topNavigation(false) // Deshabilitar navegación superior
            ->darkMode(true); // Habilitar el modo oscuro
    }
}
