<?php

declare(strict_types=1);

use App\Enums\ProductModule;
use App\Filament\Resources\KnowledgeGuides\KnowledgeGuideResource;
use App\Models\User;
use App\Support\AppGuide\AppGuide;
use App\Support\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('builds module-aware tour steps for dms only', function (): void {
    config()->set('modules.enabled', ['dms']);

    $ids = app(AppGuide::class)->stepIds();

    expect($ids)->toContain('dms', 'approvals', 'help', 'settings', 'identity', 'finish')
        ->and($ids)->not->toContain('qms')
        ->and($ids)->not->toContain('ai');
});

it('includes qms and ai steps when those modules are enabled', function (): void {
    config()->set('modules.enabled', ['dms', 'qms', 'ai']);

    $ids = app(AppGuide::class)->stepIds();

    expect($ids)->toContain('qms', 'ai', 'finish');
});

it('exposes a resolvable knowledge library url in the payload', function (): void {
    config()->set('modules.enabled', ['dms']);

    $payload = app(AppGuide::class)->payload();

    expect($payload['knowledgeLibraryUrl'])
        ->toBe(KnowledgeGuideResource::getUrl('index'))
        ->and($payload['completeUrl'])->toBe(route('app-guide.complete'))
        ->and($payload['restartUrl'])->toBe(route('app-guide.restart'));
});

it('marks the app guide completed for an authenticated user', function (): void {
    config()->set('modules.enabled', ['dms']);

    $user = User::factory()->create(['app_guide_completed_at' => null]);

    $this->actingAs($user)
        ->postJson(route('app-guide.complete'))
        ->assertSuccessful()
        ->assertJson(['completed' => true]);

    expect($user->fresh()->hasCompletedAppGuide())->toBeTrue();
});

it('resets the app guide completion for an authenticated user', function (): void {
    config()->set('modules.enabled', ['dms']);

    $user = User::factory()->create(['app_guide_completed_at' => now()]);

    $this->actingAs($user)
        ->postJson(route('app-guide.restart'))
        ->assertSuccessful()
        ->assertJson(['completed' => false]);

    expect($user->fresh()->hasCompletedAppGuide())->toBeFalse();
});

it('protects complete and restart endpoints with auth middleware', function (): void {
    foreach (['app-guide.complete', 'app-guide.restart'] as $routeName) {
        $middleware = Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];

        expect($middleware)->toContain('auth')
            ->and($middleware)->toContain('module:dms');
    }
});

it('does not re-stamp completion when already completed', function (): void {
    config()->set('modules.enabled', ['dms']);

    $completedAt = now()->subDay()->startOfSecond();
    $user = User::factory()->create(['app_guide_completed_at' => $completedAt]);

    $this->actingAs($user)
        ->postJson(route('app-guide.complete'))
        ->assertSuccessful();

    expect($user->fresh()->app_guide_completed_at?->eq($completedAt))->toBeTrue();
});

it('resolves module manager for step filtering', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    expect(app(ModuleManager::class)->enabled(ProductModule::QMS))->toBeTrue()
        ->and(app(ModuleManager::class)->enabled(ProductModule::AI))->toBeFalse();
});
