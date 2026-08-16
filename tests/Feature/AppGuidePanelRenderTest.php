<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Vite as ViteFacade;

uses(RefreshDatabase::class);

it('renders the app guide bootstrap markup for authenticated panel users', function (): void {
    config()->set('modules.enabled', ['dms', 'qms', 'ai']);

    $user = User::factory()->create([
        'app_guide_completed_at' => null,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $html = view('filament.hooks.app-guide')->render();

    expect($html)
        ->toContain('id="qualigxp-app-guide"')
        ->toContain('data-auto-start="1"')
        ->toContain('id="qualigxp-app-guide-config"')
        ->toContain('app-guide\\/complete');
});

it('does not auto-start after the guide is completed', function (): void {
    config()->set('modules.enabled', ['dms']);

    $user = User::factory()->create([
        'app_guide_completed_at' => now(),
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(view('filament.hooks.app-guide')->render())
        ->toContain('data-auto-start="0"');
});

it('publishes filament assets without copying the vite app guide bundle', function (): void {
    $this->artisan('filament:assets')->assertSuccessful();
});

it('does not register a copyable filament script for the app guide', function (): void {
    Filament::getPanel('admin');

    $appGuideScripts = collect(FilamentAsset::getScripts())
        ->filter(fn ($asset): bool => $asset->getId() === 'qualigxp-app-guide');

    expect($appGuideScripts)->toBeEmpty();

    foreach (FilamentAsset::getScripts() as $asset) {
        if ($asset->isRemote()) {
            continue;
        }

        expect($asset->getPath())->not->toBeNull();
    }
});

it('injects the app guide vite bundle for authenticated panel users', function (): void {
    ViteFacade::clearResolvedInstance();
    $this->swap(Vite::class, new class extends Vite
    {
        public function toHtml(): string
        {
            return 'VITE_APP_GUIDE:'.implode(',', $this->entryPoints);
        }
    });

    $user = User::factory()->create();

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();

    expect((string) FilamentView::renderHook(PanelsRenderHook::HEAD_END))
        ->toContain('VITE_APP_GUIDE:resources/js/app-guide.js');
});

it('does not inject the app guide vite bundle for guests', function (): void {
    ViteFacade::clearResolvedInstance();
    $this->swap(Vite::class, new class extends Vite
    {
        public function toHtml(): string
        {
            return 'VITE_APP_GUIDE:'.implode(',', $this->entryPoints);
        }
    });

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();

    expect((string) FilamentView::renderHook(PanelsRenderHook::HEAD_END))
        ->not->toContain('VITE_APP_GUIDE:');
});
