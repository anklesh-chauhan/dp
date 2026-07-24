<?php

declare(strict_types=1);

use App\Enums\ProductModule;
use App\Exceptions\ModuleNotEnabledException;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Facades\Route;

it('reports configured modules and their dependencies as enabled', function (): void {
    config()->set('modules.enabled', ['dms', 'qms', 'ai']);

    $moduleManager = app(ModuleManager::class);

    expect($moduleManager->enabled(ProductModule::DMS))->toBeTrue()
        ->and($moduleManager->enabled(ProductModule::QMS))->toBeTrue()
        ->and($moduleManager->enabled(ProductModule::AI))->toBeTrue()
        ->and($moduleManager->enabledModules())->toBe([
            ProductModule::DMS,
            ProductModule::QMS,
            ProductModule::AI,
        ]);
});

it('disables dependent modules when DMS is unavailable', function (): void {
    config()->set('modules.enabled', ['qms', 'ai']);

    $moduleManager = app(ModuleManager::class);

    expect($moduleManager->enabled(ProductModule::DMS))->toBeFalse()
        ->and($moduleManager->enabled(ProductModule::QMS))->toBeFalse()
        ->and($moduleManager->enabled(ProductModule::AI))->toBeFalse();
});

it('throws a module exception for a disabled entitlement', function (): void {
    config()->set('modules.enabled', ['dms']);

    expect(fn () => app(ModuleManager::class)->ensureEnabled(ProductModule::QMS))
        ->toThrow(ModuleNotEnabledException::class);
});

it('protects routes with the module middleware', function (): void {
    Route::middleware('module:qms')->get('/test-qms-module', fn (): string => 'QMS enabled');

    config()->set('modules.enabled', ['dms']);
    $this->get('/test-qms-module')->assertNotFound();

    config()->set('modules.enabled', ['dms', 'qms']);
    $this->get('/test-qms-module')->assertOk()->assertSee('QMS enabled');
});
