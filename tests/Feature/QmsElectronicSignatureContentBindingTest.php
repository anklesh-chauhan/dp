<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\BatchReleaseStatus;
use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Models\BatchRelease;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Services\BatchReleaseTransitionService;
use App\Domain\QMS\Services\ChangeControlTransitionService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Domain\Shared\Contracts\HasSignatureContentDigest;
use App\Domain\Shared\Services\ElectronicSignatureContentDigester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('binds change control signatures to a stored content snapshot', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach (['Review:ChangeControl', 'Approve:ChangeControl'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $approver = User::factory()->create();
    $approver->givePermissionTo(['Review:ChangeControl', 'Approve:ChangeControl']);
    $changeControl = ChangeControl::factory()->create([
        'title' => 'Signed change',
        'status' => ChangeControlStatus::Submitted,
        'submitted_at' => now(),
    ]);
    $service = app(ChangeControlTransitionService::class);
    $service->transition($changeControl, ChangeControlStatus::UnderReview, $approver, 'Quality review started.');
    $service->transition(
        $changeControl,
        ChangeControlStatus::Approved,
        $approver,
        'Benefits outweigh the controlled risks.',
        ipAddress: '203.0.113.25',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );

    $event = $changeControl->auditEvents()->whereNotNull('signature_hash')->firstOrFail();
    $verifier = app(ElectronicSignatureVerifier::class);
    $digester = app(ElectronicSignatureContentDigester::class);
    $storedDigest = $event->signatureContentDigest();
    $liveDigest = $digester->digest($changeControl->fresh()->electronicSignatureContentPayload());

    expect($event)->toBeInstanceOf(HasSignatureContentDigest::class)
        ->and($storedDigest)->not->toBeNull()
        ->and($storedDigest)->toBe($liveDigest)
        ->and($verifier->isValid($event))->toBeTrue();

    $tampered = $event->replicate();
    $tampered->context = [
        ...($event->context ?? []),
        'content_digest' => str_repeat('a', 64),
    ];

    expect($verifier->isValid($tampered))->toBeFalse();

    $changeControl->update(['title' => 'Rewritten after signature']);

    expect($verifier->isValid($event->fresh()))->toBeTrue()
        ->and($digester->digest($changeControl->fresh()->electronicSignatureContentPayload()))
        ->not->toBe($storedDigest);
});

it('binds batch release signatures to a stored content snapshot', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach (['Review:BatchRelease', 'Release:BatchRelease'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $creator = User::factory()->create();
    $releaser = User::factory()->create();
    $creator->givePermissionTo(['Review:BatchRelease', 'Release:BatchRelease']);
    $releaser->givePermissionTo(['Review:BatchRelease', 'Release:BatchRelease']);
    $release = BatchRelease::factory()->create([
        'product_name' => 'Signed batch',
        'created_by' => $creator->id,
        'owner_id' => $creator->id,
    ]);
    $service = app(BatchReleaseTransitionService::class);
    $service->transition(
        $release,
        BatchReleaseStatus::UnderReview,
        $creator,
        'Batch record complete and ready for independent release.',
    );
    $service->transition(
        $release->fresh(),
        BatchReleaseStatus::Released,
        $releaser,
        'Independent quality release.',
        ipAddress: '203.0.113.40',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );

    $event = $release->auditEvents()->whereNotNull('signature_hash')->firstOrFail();
    $verifier = app(ElectronicSignatureVerifier::class);
    $digester = app(ElectronicSignatureContentDigester::class);
    $storedDigest = $event->signatureContentDigest();

    expect($storedDigest)->not->toBeNull()
        ->and($verifier->isValid($event))->toBeTrue();

    $tampered = $event->replicate();
    $tampered->context = [
        ...($event->context ?? []),
        'content_digest' => str_repeat('b', 64),
    ];

    expect($verifier->isValid($tampered))->toBeFalse();

    $release->update(['product_name' => 'Rewritten after certification']);

    expect($verifier->isValid($event->fresh()))->toBeTrue()
        ->and($digester->digest($release->fresh()->electronicSignatureContentPayload()))
        ->not->toBe($storedDigest);
});
