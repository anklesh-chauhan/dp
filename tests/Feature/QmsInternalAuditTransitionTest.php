<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\InternalAudit;
use App\Domain\QMS\Models\InternalAuditEvent;
use App\Domain\QMS\Services\InternalAuditTransitionService;
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
        'Schedule:InternalAudit', 'Conduct:InternalAudit', 'Report:InternalAudit',
        'FollowUp:InternalAudit', 'Close:InternalAudit', 'Manage:InternalAudit',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->audit = InternalAudit::factory()->create();
});

it('records milestones append-only history and signed consequential decisions', function (): void {
    $service = app(InternalAuditTransitionService::class);
    $service->transition($this->audit, InternalAuditStatus::Scheduled, $this->actor, 'Audit plan approved.');
    $service->transition($this->audit, InternalAuditStatus::InProgress, $this->actor, 'Opening meeting completed.');
    $reporting = $service->transition(
        $this->audit, InternalAuditStatus::Reporting, $this->actor, 'Fieldwork completed.',
        ipAddress: '203.0.113.55', userAgent: 'DocuPharma-QMS-Test/1.0',
    );
    $reporting->update(['report_issued_at' => now()]);
    $closed = $service->transition($reporting, InternalAuditStatus::Closed, $this->actor, 'Report accepted; no follow-up required.');
    $events = $closed->auditEvents()->orderBy('id')->get();
    $signedEvent = $events->get(2);

    expect($closed->status)->toBe(InternalAuditStatus::Closed)
        ->and($closed->started_at)->not->toBeNull()
        ->and($closed->completed_at)->not->toBeNull()
        ->and($closed->closed_at)->not->toBeNull()
        ->and($events)->toHaveCount(4)
        ->and($events->first()->signature_hash)->toBeNull()
        ->and($signedEvent?->signatureMeaning())->toBe(InternalAuditStatus::Reporting->value)
        ->and($signedEvent?->signatureIpAddress())->toBe('203.0.113.55')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signedEvent))->toBeTrue();

    expect(fn () => $signedEvent?->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('enforces schedule report and follow-up milestones', function (): void {
    $service = app(InternalAuditTransitionService::class);
    $this->audit->update(['scheduled_end_at' => $this->audit->scheduled_start_at?->subDay()]);

    expect(fn () => $service->transition(
        $this->audit, InternalAuditStatus::Scheduled, $this->actor, 'Invalid schedule.',
    ))->toThrow(ValidationException::class);

    $this->audit->update(['status' => InternalAuditStatus::Reporting, 'report_issued_at' => null]);

    expect(fn () => $service->transition(
        $this->audit, InternalAuditStatus::Closed, $this->actor, 'Missing report.',
    ))->toThrow(ValidationException::class)
        ->and(InternalAuditEvent::query()->count())->toBe(0);
});

it('prevents audit closure until every finding has a terminal disposition', function (): void {
    $this->audit->update([
        'status' => InternalAuditStatus::Reporting,
        'report_issued_at' => now(),
    ]);
    $openFinding = AuditFinding::factory()->create([
        'internal_audit_id' => $this->audit,
        'disposition' => AuditFindingDisposition::Accepted,
    ]);
    $service = app(InternalAuditTransitionService::class);

    expect(fn () => $service->transition(
        $this->audit,
        InternalAuditStatus::Closed,
        $this->actor,
        'Premature closure.',
    ))->toThrow(ValidationException::class);

    $openFinding->update(['disposition' => AuditFindingDisposition::Closed]);
    AuditFinding::factory()->create([
        'internal_audit_id' => $this->audit,
        'disposition' => AuditFindingDisposition::Rejected,
    ]);
    AuditFinding::factory()->create([
        'internal_audit_id' => $this->audit,
        'disposition' => AuditFindingDisposition::Cancelled,
    ]);

    $closed = $service->transition(
        $this->audit,
        InternalAuditStatus::Closed,
        $this->actor,
        'All findings reached terminal disposition.',
    );

    expect($closed->status)->toBe(InternalAuditStatus::Closed)
        ->and($closed->auditEvents)->toHaveCount(1);
});

it('rejects unauthorized invalid and disabled transitions without events', function (): void {
    $service = app(InternalAuditTransitionService::class);

    expect(fn () => $service->transition(
        $this->audit, InternalAuditStatus::Scheduled, User::factory()->create(), 'Unauthorized.',
    ))->toThrow(AuthorizationException::class);

    expect(fn () => $service->transition(
        $this->audit, InternalAuditStatus::Closed, $this->actor, 'Invalid direct closure.',
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->audit, InternalAuditStatus::Scheduled, $this->actor, 'Disabled.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(InternalAuditEvent::query()->count())->toBe(0);
});
