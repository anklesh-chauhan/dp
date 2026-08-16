<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\Component;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->databaseNotifications()
            ->brandName('QualiGxP')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make('AI Management')->icon(Heroicon::Sparkles),
                NavigationGroup::make('DMS')->icon(Heroicon::DocumentText),
                NavigationGroup::make('DMS · Reports')->icon(Heroicon::OutlinedChartBar),
                NavigationGroup::make('QMS')->icon(Heroicon::OutlinedClipboardDocumentCheck),
                NavigationGroup::make('DMS · Help & Knowledge')->icon(Heroicon::OutlinedBookOpen),
                NavigationGroup::make('DMS · Settings')->icon(Heroicon::OutlinedSquares2x2),
                NavigationGroup::make('Core · Identity & Access')->icon(Heroicon::UserGroup),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->pages([
                // Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                //
            ])
            ->middleware([
                'module:dms',
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()->navigationGroup('Core · Identity & Access'),
            ])
            ->userMenuItems([
                Action::make('restartAppGuide')
                    ->label('Restart app guide')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->visible(fn (): bool => Auth::check())
                    ->action(function (Component $livewire): void {
                        Auth::user()?->resetAppGuide();

                        $livewire->js(
                            'window.dispatchEvent(new CustomEvent("qualigxp-app-guide-restart"))'
                        );
                    }),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Auth::check()
                    ? Vite::withEntryPoints(['resources/js/app-guide.js'])->toHtml()
                    : '',
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Auth::check()
                    ? view('filament.hooks.app-guide')->render()
                    : '',
            )
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('w-64')
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
