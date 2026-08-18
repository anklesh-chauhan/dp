<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Services\ChangeControlTransitionService;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Services\ElectronicSignatureAuthenticator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach ([
        'Review:ChangeControl',
        'Approve:ChangeControl',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->approver = User::factory()->create();
    $this->approver->givePermissionTo([
        'Review:ChangeControl',
        'Approve:ChangeControl',
    ]);
    $this->changeControl = ChangeControl::factory()->create([
        'status' => ChangeControlStatus::UnderReview,
        'submitted_at' => now(),
    ]);
});

it('does not persist a signature when the password challenge fails', function (): void {
    $authenticator = app(ElectronicSignatureAuthenticator::class);

    expect(fn () => $authenticator->confirm($this->approver, 'wrong-password'))
        ->toThrow(ValidationException::class);

    expect(fn () => app(ChangeControlTransitionService::class)->transition(
        $this->changeControl,
        ChangeControlStatus::Approved,
        $this->approver,
        'Benefits outweigh the controlled risks.',
    ))->toThrow(ValidationException::class);

    expect($this->changeControl->fresh()->status)->toBe(ChangeControlStatus::UnderReview)
        ->and($this->changeControl->auditEvents()->where('to_status', ChangeControlStatus::Approved)->exists())->toBeFalse();
});

it('rejects a session-only signature when the factory password bypass cannot apply', function (): void {
    $this->approver->forceFill(['password' => 'DifferentPass1!x'])->save();

    expect(fn () => app(ElectronicSignatureHasher::class)->issueFor(
        signer: $this->approver,
        recordKey: 'signature-test',
        meaning: 'approved',
        signedAt: now(),
        reason: 'Session click only.',
        ipAddress: '203.0.113.10',
        userAgent: 'QualiGxP-Test/1.0',
    ))->toThrow(ValidationException::class);

    expect(fn () => app(ChangeControlTransitionService::class)->transition(
        $this->changeControl,
        ChangeControlStatus::Approved,
        $this->approver,
        'Benefits outweigh the controlled risks.',
    ))->toThrow(ValidationException::class);

    expect($this->changeControl->fresh()->status)->toBe(ChangeControlStatus::UnderReview);
});

it('blocks deactivated users from applying an electronic signature', function (): void {
    $this->approver->forceFill(['deactivated_at' => now()])->save();

    expect(fn () => app(ElectronicSignatureHasher::class)->issueFor(
        signer: $this->approver->fresh(),
        recordKey: 'signature-test',
        meaning: 'approved',
        signedAt: now(),
        reason: 'Should not sign.',
        ipAddress: '203.0.113.10',
        userAgent: 'QualiGxP-Test/1.0',
    ))->toThrow(ValidationException::class);
});
