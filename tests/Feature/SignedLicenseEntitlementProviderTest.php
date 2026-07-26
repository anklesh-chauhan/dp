<?php

declare(strict_types=1);

use App\Enums\ProductLicenseState;
use App\Enums\ProductModule;
use App\Models\ProductLicense;
use App\Support\Modules\ConfiguredModuleEntitlementProvider;
use App\Support\Modules\Contracts\LicenseLifecycleEvaluator;
use App\Support\Modules\Contracts\ModuleEntitlementProvider;
use App\Support\Modules\SignedLicenseEntitlementProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns module claims from a verified active license', function (): void {
    $license = ProductLicense::factory()->create([
        'payload' => json_encode([
            'modules' => ['dms', 'qms'],
        ], JSON_THROW_ON_ERROR),
    ]);

    bindLicenseStates([$license->id => ProductLicenseState::Active]);

    expect(app(SignedLicenseEntitlementProvider::class)->modules())->toBe([
        ProductModule::DMS,
        ProductModule::QMS,
    ]);
});

it('selects an older grace license when a newer license is unavailable', function (): void {
    $graceLicense = ProductLicense::factory()->create([
        'payload' => json_encode([
            'modules' => ['dms', 'ai'],
        ], JSON_THROW_ON_ERROR),
        'activated_at' => now()->subDay(),
    ]);
    $expiredLicense = ProductLicense::factory()->create([
        'payload' => json_encode([
            'modules' => ['dms', 'qms'],
        ], JSON_THROW_ON_ERROR),
        'activated_at' => now(),
    ]);

    bindLicenseStates([
        $expiredLicense->id => ProductLicenseState::Expired,
        $graceLicense->id => ProductLicenseState::Grace,
    ]);

    expect(app(SignedLicenseEntitlementProvider::class)->modules())->toBe([
        ProductModule::DMS,
        ProductModule::AI,
    ]);
});

it('uses signed issue time instead of local activation order for precedence', function (): void {
    $newerIssuedLicense = ProductLicense::factory()->create([
        'payload' => json_encode([
            'modules' => ['dms', 'qms'],
        ], JSON_THROW_ON_ERROR),
        'issued_at' => now()->subDay(),
        'activated_at' => now()->subDay(),
    ]);
    $laterActivatedOlderLicense = ProductLicense::factory()->create([
        'payload' => json_encode([
            'modules' => ['dms', 'ai'],
        ], JSON_THROW_ON_ERROR),
        'issued_at' => now()->subDays(2),
        'activated_at' => now(),
    ]);

    bindLicenseStates([
        $newerIssuedLicense->id => ProductLicenseState::Active,
        $laterActivatedOlderLicense->id => ProductLicenseState::Active,
    ]);

    expect(app(SignedLicenseEntitlementProvider::class)->modules())->toBe([
        ProductModule::DMS,
        ProductModule::QMS,
    ]);
});

it('fails closed when no usable license exists', function (ProductLicenseState $state): void {
    $license = ProductLicense::factory()->create([
        'payload' => json_encode([
            'modules' => ['dms', 'qms', 'ai'],
        ], JSON_THROW_ON_ERROR),
    ]);

    bindLicenseStates([$license->id => $state]);

    expect(app(SignedLicenseEntitlementProvider::class)->modules())->toBe([]);
})->with([
    ProductLicenseState::Invalid,
    ProductLicenseState::Revoked,
    ProductLicenseState::Expired,
]);

it('fails closed for malformed or dependency-invalid signed claims', function (string $payload): void {
    $license = ProductLicense::factory()->create([
        'payload' => $payload,
    ]);

    bindLicenseStates([$license->id => ProductLicenseState::Active]);

    expect(app(SignedLicenseEntitlementProvider::class)->modules())->toBe([]);
})->with([
    'malformed JSON' => '{',
    'unknown module' => ['{"modules":["dms","unknown"]}'],
    'missing DMS dependency' => ['{"modules":["qms"]}'],
    'duplicate module' => ['{"modules":["dms","dms"]}'],
]);

it('remains unbound while environment entitlements are authoritative', function (): void {
    expect(app(ModuleEntitlementProvider::class))
        ->toBeInstanceOf(ConfiguredModuleEntitlementProvider::class)
        ->not->toBeInstanceOf(SignedLicenseEntitlementProvider::class);
});

/**
 * @param  array<int, ProductLicenseState>  $states
 */
function bindLicenseStates(array $states): void
{
    $evaluator = Mockery::mock(LicenseLifecycleEvaluator::class);
    $evaluator->shouldReceive('evaluate')
        ->andReturnUsing(
            fn (ProductLicense $license): ProductLicenseState => $states[$license->id],
        );

    app()->instance(LicenseLifecycleEvaluator::class, $evaluator);
}
