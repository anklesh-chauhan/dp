<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        ->toContain('id="docupharma-app-guide"')
        ->toContain('data-auto-start="1"')
        ->toContain('id="docupharma-app-guide-config"')
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
