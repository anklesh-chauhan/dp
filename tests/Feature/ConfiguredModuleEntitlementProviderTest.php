<?php

declare(strict_types=1);

use App\Enums\ProductModule;
use App\Support\Modules\ConfiguredModuleEntitlementProvider;
use App\Support\Modules\Contracts\ModuleEntitlementProvider;
use App\Support\Modules\ModuleManager;

it('binds the environment configuration entitlement provider', function (): void {
    expect(app(ModuleEntitlementProvider::class))
        ->toBeInstanceOf(ConfiguredModuleEntitlementProvider::class);
});

it('normalizes configured module values without changing their meaning', function (): void {
    config()->set('modules.enabled', [
        ' DMS ',
        ProductModule::QMS,
        'ai',
        'unknown',
        '',
        'dms',
    ]);

    expect(app(ModuleEntitlementProvider::class)->modules())->toBe([
        ProductModule::DMS,
        ProductModule::QMS,
        ProductModule::AI,
    ]);
});

it('allows module manager consumers to use a replacement entitlement provider', function (): void {
    $provider = Mockery::mock(ModuleEntitlementProvider::class);
    $provider->shouldReceive('modules')
        ->andReturn([
            ProductModule::DMS,
            ProductModule::QMS,
        ]);
    app()->instance(ModuleEntitlementProvider::class, $provider);

    $moduleManager = app(ModuleManager::class);

    expect($moduleManager->enabled(ProductModule::DMS))->toBeTrue()
        ->and($moduleManager->enabled(ProductModule::QMS))->toBeTrue()
        ->and($moduleManager->enabled(ProductModule::AI))->toBeFalse();
});
