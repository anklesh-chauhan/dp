<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\Investigation;
use App\Domain\QMS\Models\InvestigationAuditEvent;
use App\Domain\QMS\Services\InvestigationTransitionService;
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
        'Update:Investigation',
        'Review:Investigation',
        'Complete:Investigation',
        'Manage:Investigation',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->investigation = Investigation::factory()->create([
        'deviation_id' => Deviation::factory(),
        'root_cause' => 'Preventive maintenance frequency was inadequate.',
        'conclusion' => 'Revise the maintenance strategy and create CAPA.',
    ]);
});

it('enforces investigation lifecycle and signs completion', function (): void {
    $service = app(InvestigationTransitionService::class);
    $service->transition($this->investigation, InvestigationStatus::InProgress, $this->actor);
    $service->transition($this->investigation, InvestigationStatus::PendingReview, $this->actor);
    $completed = $service->transition(
        $this->investigation,
        InvestigationStatus::Completed,
        $this->actor,
        'Root cause and conclusions approved.',
        ipAddress: '203.0.113.31',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );

    $events = $completed->auditEvents()->orderBy('id')->get();
    $completionEvent = $events->last();

    expect($completed->status)->toBe(InvestigationStatus::Completed)
        ->and($completed->started_at)->not->toBeNull()
        ->and($completed->completed_at)->not->toBeNull()
        ->and($events)->toHaveCount(3)
        ->and($completionEvent->signatureMeaning())->toBe(InvestigationStatus::Completed->value)
        ->and($completionEvent->signatureSignerId())->toBe($this->actor->id)
        ->and(app(ElectronicSignatureVerifier::class)->isValid($completionEvent))->toBeTrue();

    expect(fn () => $completionEvent->delete())
        ->toThrow(LogicException::class);
});

it('requires root cause and conclusion before completion', function (): void {
    $this->investigation->update([
        'status' => InvestigationStatus::PendingReview,
        'root_cause' => null,
        'conclusion' => null,
    ]);

    expect(fn () => app(InvestigationTransitionService::class)->transition(
        $this->investigation,
        InvestigationStatus::Completed,
        $this->actor,
    ))->toThrow(ValidationException::class)
        ->and($this->investigation->fresh()?->status)->toBe(InvestigationStatus::PendingReview)
        ->and(InvestigationAuditEvent::query()->count())->toBe(0);
});

it('rejects unauthorized invalid and disabled investigation transitions', function (): void {
    expect(fn () => app(InvestigationTransitionService::class)->transition(
        $this->investigation,
        InvestigationStatus::InProgress,
        User::factory()->create(),
    ))->toThrow(AuthorizationException::class);

    expect(fn () => app(InvestigationTransitionService::class)->transition(
        $this->investigation,
        InvestigationStatus::Completed,
        $this->actor,
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => app(InvestigationTransitionService::class)->transition(
        $this->investigation,
        InvestigationStatus::InProgress,
        $this->actor,
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(InvestigationAuditEvent::query()->count())->toBe(0);
});
