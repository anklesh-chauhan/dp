<?php

declare(strict_types=1);

use App\Enums\ProductLicenseState;
use App\Models\ProductLicense;
use App\Support\Modules\Contracts\LicenseLifecycleEvaluator;
use App\Support\Modules\Contracts\SignedLicenseVerifier;
use Carbon\CarbonImmutable;

it('classifies verified licenses across active grace expired and revoked states', function (
    string $evaluatedAt,
    ?string $revokedAt,
    ProductLicenseState $expectedState,
): void {
    $license = ProductLicense::factory()->make([
        'expires_at' => '2027-07-25T00:00:00+00:00',
        'grace_ends_at' => '2027-08-08T00:00:00+00:00',
        'revoked_at' => $revokedAt,
    ]);

    $verifier = Mockery::mock(SignedLicenseVerifier::class);
    $verifier->shouldReceive('isValid')
        ->once()
        ->with($license)
        ->andReturnTrue();
    app()->instance(SignedLicenseVerifier::class, $verifier);

    expect(app(LicenseLifecycleEvaluator::class)->evaluate(
        $license,
        CarbonImmutable::parse($evaluatedAt),
    ))->toBe($expectedState);
})->with([
    'active before expiry' => [
        '2027-07-24T23:59:59+00:00',
        null,
        ProductLicenseState::Active,
    ],
    'active at expiry boundary' => [
        '2027-07-25T00:00:00+00:00',
        null,
        ProductLicenseState::Active,
    ],
    'grace after expiry' => [
        '2027-07-26T00:00:00+00:00',
        null,
        ProductLicenseState::Grace,
    ],
    'grace at boundary' => [
        '2027-08-08T00:00:00+00:00',
        null,
        ProductLicenseState::Grace,
    ],
    'expired after grace' => [
        '2027-08-08T00:00:01+00:00',
        null,
        ProductLicenseState::Expired,
    ],
    'revoked before expiry' => [
        '2027-07-20T00:00:00+00:00',
        '2027-07-19T00:00:00+00:00',
        ProductLicenseState::Revoked,
    ],
]);

it('classifies a license with a failed signature re-verification as invalid', function (): void {
    $license = ProductLicense::factory()->make();

    $verifier = Mockery::mock(SignedLicenseVerifier::class);
    $verifier->shouldReceive('isValid')
        ->once()
        ->with($license)
        ->andReturnFalse();
    app()->instance(SignedLicenseVerifier::class, $verifier);

    expect(app(LicenseLifecycleEvaluator::class)->evaluate($license))
        ->toBe(ProductLicenseState::Invalid);
});

it('keeps a future revocation inactive until its effective time', function (): void {
    $license = ProductLicense::factory()->make([
        'expires_at' => '2027-07-25T00:00:00+00:00',
        'grace_ends_at' => '2027-08-08T00:00:00+00:00',
        'revoked_at' => '2027-07-22T00:00:00+00:00',
    ]);

    $verifier = Mockery::mock(SignedLicenseVerifier::class);
    $verifier->shouldReceive('isValid')->once()->andReturnTrue();
    app()->instance(SignedLicenseVerifier::class, $verifier);

    expect(app(LicenseLifecycleEvaluator::class)->evaluate(
        $license,
        CarbonImmutable::parse('2027-07-21T00:00:00+00:00'),
    ))->toBe(ProductLicenseState::Active);
});
