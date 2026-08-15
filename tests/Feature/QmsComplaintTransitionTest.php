<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\ComplaintAuditEvent;
use App\Domain\QMS\Services\ComplaintTransitionService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'Assess:Complaint',
        'Investigate:Complaint',
        'Respond:Complaint',
        'Close:Complaint',
        'Manage:Complaint',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->complaint = Complaint::factory()->create();
});

it('records an attributable timeline and signs consequential complaint decisions', function (): void {
    $service = app(ComplaintTransitionService::class);
    $service->transition($this->complaint, ComplaintStatus::Received, $this->actor, 'Complaint intake completed.');
    $assessed = $service->transition($this->complaint, ComplaintStatus::UnderAssessment, $this->actor, 'Assessment accepted.');
    $assessed->update([
        'adverse_event_suspected' => false,
        'regulatory_reportable' => false,
    ]);
    $service->transition($this->complaint, ComplaintStatus::ResponsePending, $this->actor, 'Approved response prepared.');
    $closed = $service->transition(
        $this->complaint,
        ComplaintStatus::Closed,
        $this->actor,
        'Complaint response issued and case closed.',
        ['signature' => 'must-not-be-recorded', 'channel' => 'email'],
        '203.0.113.41',
        'QualiGxP-QMS-Test/1.0',
    );

    $events = $closed->auditEvents()->orderBy('id')->get();
    $signedEvent = $events->last();

    expect($closed->status)->toBe(ComplaintStatus::Closed)
        ->and($closed->acknowledged_at)->not->toBeNull()
        ->and($closed->closed_at)->not->toBeNull()
        ->and($events)->toHaveCount(4)
        ->and($events->first()->signature_hash)->toBeNull()
        ->and($signedEvent->signatureMeaning())->toBe(ComplaintStatus::Closed->value)
        ->and($signedEvent->signatureSignerId())->toBe($this->actor->id)
        ->and($signedEvent->signatureIpAddress())->toBe('203.0.113.41')
        ->and($signedEvent->context)->toBe(['channel' => 'email'])
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signedEvent))->toBeTrue();

    expect(fn () => $signedEvent->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('requires regulatory assessment and reporting evidence before disposition', function (): void {
    $this->complaint->update(['status' => ComplaintStatus::UnderAssessment]);
    $service = app(ComplaintTransitionService::class);

    expect(fn () => $service->transition(
        $this->complaint,
        ComplaintStatus::ResponsePending,
        $this->actor,
        'Response drafted.',
    ))->toThrow(ValidationException::class);

    $this->complaint->update([
        'adverse_event_suspected' => true,
        'regulatory_reportable' => true,
        'status' => ComplaintStatus::ResponsePending,
    ]);

    expect(fn () => $service->transition(
        $this->complaint,
        ComplaintStatus::Closed,
        $this->actor,
        'Closing reportable complaint.',
    ))->toThrow(ValidationException::class)
        ->and(ComplaintAuditEvent::query()->count())->toBe(0);
});

it('rejects missing reasons unauthorized invalid and disabled transitions without events', function (): void {
    $service = app(ComplaintTransitionService::class);

    expect(fn () => $service->transition(
        $this->complaint,
        ComplaintStatus::Received,
        $this->actor,
        ' ',
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->transition(
        $this->complaint,
        ComplaintStatus::Received,
        User::factory()->create(),
        'Complaint received.',
    ))->toThrow(AuthorizationException::class);

    expect(fn () => $service->transition(
        $this->complaint,
        ComplaintStatus::Closed,
        $this->actor,
        'Invalid direct closure.',
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->complaint,
        ComplaintStatus::Received,
        $this->actor,
        'Complaint received.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(ComplaintAuditEvent::query()->count())->toBe(0);
});
