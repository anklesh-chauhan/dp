<?php

declare(strict_types=1);

use App\Enums\ProductLicenseState;
use App\Enums\ProductModule;
use App\Models\ProductLicense;
use App\Support\Modules\ConfiguredModuleEntitlementProvider;
use App\Support\Modules\Contracts\LicenseLifecycleEvaluator;
use App\Support\Modules\Contracts\ModuleEntitlementProvider;
use App\Support\Modules\ModuleManager;
use App\Support\Modules\SignedLicenseEntitlementProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('uses environment entitlements by default', function (): void {
    config()->set('modules.enabled', ['dms', 'ai']);

    expect(app(ModuleEntitlementProvider::class))
        ->toBeInstanceOf(ConfiguredModuleEntitlementProvider::class)
        ->and(app(ModuleManager::class)->enabledModules())->toBe([
            ProductModule::DMS,
            ProductModule::AI,
        ]);
});

it('uses signed license claims when explicitly selected', function (
    ProductLicenseState $state,
    bool $expectedQmsAccess,
): void {
    config()->set('modules.entitlement_source', 'signed_license');
    config()->set('modules.enabled', ['dms', 'qms', 'ai']);

    $license = ProductLicense::factory()->create([
        'payload' => json_encode([
            'modules' => ['dms', 'qms'],
        ], JSON_THROW_ON_ERROR),
    ]);

    $evaluator = Mockery::mock(LicenseLifecycleEvaluator::class);
    $evaluator->shouldReceive('evaluate')
        ->with(Mockery::on(fn (ProductLicense $candidate): bool => $candidate->is($license)))
        ->andReturn($state);
    app()->instance(LicenseLifecycleEvaluator::class, $evaluator);

    Route::middleware('module:qms')->get(
        '/test-signed-license-qms-module',
        fn (): string => 'QMS licensed',
    );

    expect(app(ModuleEntitlementProvider::class))
        ->toBeInstanceOf(SignedLicenseEntitlementProvider::class)
        ->and(app(ModuleManager::class)->enabled(ProductModule::QMS))
        ->toBe($expectedQmsAccess);

    $response = $this->get('/test-signed-license-qms-module');

    if ($expectedQmsAccess) {
        $response->assertOk()->assertSee('QMS licensed');
    } else {
        $response->assertNotFound();
    }
})->with([
    'active license' => [ProductLicenseState::Active, true],
    'grace license' => [ProductLicenseState::Grace, true],
    'expired license' => [ProductLicenseState::Expired, false],
    'revoked license' => [ProductLicenseState::Revoked, false],
    'invalid license' => [ProductLicenseState::Invalid, false],
]);

it('rejects an unknown entitlement source instead of silently enabling modules', function (): void {
    config()->set('modules.entitlement_source', 'unknown');

    expect(fn () => app(ModuleEntitlementProvider::class))
        ->toThrow(InvalidArgumentException::class, 'Unknown product-module entitlement source.');
});
