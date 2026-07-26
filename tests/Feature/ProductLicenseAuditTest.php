<?php

declare(strict_types=1);

use App\Enums\ProductLicenseAuditEventType;
use App\Enums\ProductLicenseState;
use App\Models\ProductLicense;
use App\Models\ProductLicenseAuditEvent;
use App\Support\Modules\Contracts\LicenseAuditRecorder;
use App\Support\Modules\Contracts\LicenseLifecycleEvaluator;
use App\Support\Modules\Contracts\ProductLicenseRevoker;
use App\Support\Modules\Contracts\SignedLicenseVerifier;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records secret-safe append-only license events', function (): void {
    $license = ProductLicense::factory()->create();

    $event = app(LicenseAuditRecorder::class)->record(
        $license,
        ProductLicenseAuditEventType::Activated,
        ProductLicenseState::Active,
        [
            'key_id' => 'test-key',
            'modules' => ['dms'],
            'payload' => 'secret-payload',
            'signature' => 'secret-signature',
        ],
    );

    expect($event)
        ->not->toBeNull()
        ->and($event?->context)->toBe([
            'key_id' => 'test-key',
            'modules' => ['dms'],
        ])
        ->and(json_encode($event?->toArray(), JSON_THROW_ON_ERROR))
        ->not->toContain('secret-payload')
        ->not->toContain('secret-signature');

    expect(fn () => $event?->update(['context' => []]))
        ->toThrow(LogicException::class, 'append-only');
    expect(fn () => $event?->delete())
        ->toThrow(LogicException::class, 'append-only');
});

it('does not duplicate an unchanged lifecycle state', function (): void {
    $license = ProductLicense::factory()->create();
    $recorder = app(LicenseAuditRecorder::class);

    $recorder->record($license, ProductLicenseAuditEventType::GraceStarted, ProductLicenseState::Grace);
    $recorder->record($license, ProductLicenseAuditEventType::GraceStarted, ProductLicenseState::Grace);

    expect($license->auditEvents()->count())->toBe(1);
});

it('revokes a license atomically and appends the transition', function (): void {
    $license = ProductLicense::factory()->create();

    app(LicenseAuditRecorder::class)->record(
        $license,
        ProductLicenseAuditEventType::Activated,
        ProductLicenseState::Active,
    );

    $revoked = app(ProductLicenseRevoker::class)->revoke($license);
    $event = ProductLicenseAuditEvent::query()->latest('id')->firstOrFail();

    expect($revoked->revoked_at)->not->toBeNull()
        ->and($event->event_type)->toBe(ProductLicenseAuditEventType::Revoked)
        ->and($event->from_state)->toBe(ProductLicenseState::Active)
        ->and($event->to_state)->toBe(ProductLicenseState::Revoked);
});

it('audits grace expiry and verification failure transitions once', function (): void {
    $license = ProductLicense::factory()->create([
        'expires_at' => '2027-07-25T00:00:00+00:00',
        'grace_ends_at' => '2027-08-08T00:00:00+00:00',
    ]);
    app(LicenseAuditRecorder::class)->record(
        $license,
        ProductLicenseAuditEventType::Activated,
        ProductLicenseState::Active,
    );

    $verifier = Mockery::mock(SignedLicenseVerifier::class);
    $verifier->shouldReceive('isValid')->times(2)->andReturnTrue();
    app()->instance(SignedLicenseVerifier::class, $verifier);

    $evaluator = app(LicenseLifecycleEvaluator::class);
    $evaluator->evaluate($license, CarbonImmutable::parse('2027-07-26T00:00:00+00:00'));
    $evaluator->evaluate($license, CarbonImmutable::parse('2027-08-09T00:00:00+00:00'));

    $failedVerifier = Mockery::mock(SignedLicenseVerifier::class);
    $failedVerifier->shouldReceive('isValid')->once()->andReturnFalse();
    app()->instance(SignedLicenseVerifier::class, $failedVerifier);
    app(LicenseLifecycleEvaluator::class)->evaluate($license);

    expect($license->auditEvents()->orderBy('id')->pluck('event_type')->all())->toBe([
        ProductLicenseAuditEventType::Activated,
        ProductLicenseAuditEventType::GraceStarted,
        ProductLicenseAuditEventType::Expired,
        ProductLicenseAuditEventType::VerificationFailed,
    ]);
});
