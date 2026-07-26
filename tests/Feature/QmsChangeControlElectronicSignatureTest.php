<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Services\ChangeControlTransitionService;
use App\Domain\Shared\Contracts\ElectronicSignatureRecord;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
});

it('creates a reproducible Shared electronic signature for approval decisions', function (): void {
    $changeControl = ChangeControl::factory()->create([
        'status' => ChangeControlStatus::Submitted,
        'submitted_at' => now(),
    ]);
    $service = app(ChangeControlTransitionService::class);
    $service->transition(
        $changeControl,
        ChangeControlStatus::UnderReview,
        $this->approver,
        'Quality review started.',
    );
    $service->transition(
        $changeControl,
        ChangeControlStatus::Approved,
        $this->approver,
        'Benefits outweigh the controlled risks.',
        ipAddress: '203.0.113.25',
        userAgent: 'DocuPharma-QMS-Test/1.0',
    );

    $events = $changeControl->auditEvents()->orderBy('id')->get();
    $reviewEvent = $events->first();
    $approvalEvent = $events->last();

    expect($reviewEvent->signature_hash)->toBeNull()
        ->and($approvalEvent)->toBeInstanceOf(ElectronicSignatureRecord::class)
        ->and($approvalEvent->event_uuid)->not->toBeNull()
        ->and($approvalEvent->signatureMeaning())->toBe(ChangeControlStatus::Approved->value)
        ->and($approvalEvent->signatureSignerId())->toBe($this->approver->id)
        ->and($approvalEvent->signatureReason())->toBe('Benefits outweigh the controlled risks.')
        ->and($approvalEvent->signatureIpAddress())->toBe('203.0.113.25')
        ->and($approvalEvent->signatureUserAgent())->toBe('DocuPharma-QMS-Test/1.0')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($approvalEvent))->toBeTrue();

    DB::table('change_control_audit_events')
        ->where('id', $approvalEvent->id)
        ->update(['reason' => 'Tampered reason']);

    expect(app(ElectronicSignatureVerifier::class)->isValid($approvalEvent->fresh()))
        ->toBeFalse();
});
