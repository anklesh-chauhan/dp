<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Services\ScheduleMGapAssessmentTransitionService;
use App\Domain\QMS\Services\ScheduleMGapItemService;
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
        'Conduct:ScheduleMGapAssessment',
        'Approve:ScheduleMGapAssessment',
        'Close:ScheduleMGapAssessment',
        'Manage:ScheduleMGapAssessment',
        'Update:ScheduleMGapAssessment',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->owner = User::factory()->create();
    $this->creator = User::factory()->create();
    $this->assessment = ScheduleMGapAssessment::factory()->withPartIChecklist()->create([
        'owner_id' => $this->owner,
        'created_by' => $this->creator,
    ]);
});

it('records begin review approval and closure with signed consequential history', function (): void {
    $service = app(ScheduleMGapAssessmentTransitionService::class);
    $itemService = app(ScheduleMGapItemService::class);

    $service->transition($this->assessment, ScheduleMGapAssessmentStatus::InProgress, $this->actor, 'Gap assessment started.');

    foreach ($this->assessment->items as $item) {
        $itemService->updateStatus($item, $this->actor, 'Clause reviewed.', [
            'status' => ScheduleMGapItemStatus::Compliant,
            'evidence_notes' => 'Supported by effective SOP.',
        ]);
    }

    $underReview = $service->transition(
        $this->assessment->fresh(),
        ScheduleMGapAssessmentStatus::UnderReview,
        $this->actor,
        'All clauses assessed for QA review.',
    );
    $approved = $service->transition(
        $underReview,
        ScheduleMGapAssessmentStatus::Approved,
        $this->actor,
        'Independent QA approval of gap assessment.',
        ipAddress: '203.0.113.55',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );
    $closed = $service->transition(
        $approved,
        ScheduleMGapAssessmentStatus::Closed,
        $this->actor,
        'Assessment package archived.',
        ipAddress: '203.0.113.55',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );

    $transitionEvents = $closed->auditEvents()->where('event_type', 'transition')->orderBy('id')->get();
    $approvalEvent = $transitionEvents->get(2);

    expect($closed->status)->toBe(ScheduleMGapAssessmentStatus::Closed)
        ->and($closed->approved_at)->not->toBeNull()
        ->and($closed->closed_at)->not->toBeNull()
        ->and($transitionEvents)->toHaveCount(4)
        ->and($transitionEvents->first()->signature_hash)->toBeNull()
        ->and($approvalEvent?->signatureMeaning())->toBe(ScheduleMGapAssessmentStatus::Approved->value)
        ->and(app(ElectronicSignatureVerifier::class)->isValid($approvalEvent))->toBeTrue()
        ->and($closed->auditEvents()->where('event_type', 'item_update')->count())->toBeGreaterThan(0);

    expect(fn () => $approvalEvent?->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('requires assessed clauses and independent approval', function (): void {
    $service = app(ScheduleMGapAssessmentTransitionService::class);
    $service->transition($this->assessment, ScheduleMGapAssessmentStatus::InProgress, $this->actor, 'Started.');

    expect(fn () => $service->transition(
        $this->assessment->fresh(),
        ScheduleMGapAssessmentStatus::UnderReview,
        $this->actor,
        'Not ready.',
    ))->toThrow(ValidationException::class);

    $this->assessment->items()->update(['status' => ScheduleMGapItemStatus::Compliant->value]);
    $this->assessment->update([
        'status' => ScheduleMGapAssessmentStatus::UnderReview,
        'owner_id' => $this->actor->id,
    ]);

    expect(fn () => $service->transition(
        $this->assessment->fresh(),
        ScheduleMGapAssessmentStatus::Approved,
        $this->actor,
        'Self approval blocked.',
    ))->toThrow(ValidationException::class);
});

it('blocks transitions when qms is disabled or permission is missing', function (): void {
    $service = app(ScheduleMGapAssessmentTransitionService::class);

    config()->set('modules.enabled', ['dms']);
    expect(fn () => $service->transition(
        $this->assessment,
        ScheduleMGapAssessmentStatus::InProgress,
        $this->actor,
        'Disabled module.',
    ))->toThrow(ModuleNotEnabledException::class);

    config()->set('modules.enabled', ['dms', 'qms']);
    $unauthorized = User::factory()->create();
    expect(fn () => $service->transition(
        $this->assessment,
        ScheduleMGapAssessmentStatus::InProgress,
        $unauthorized,
        'No permission.',
    ))->toThrow(AuthorizationException::class);
});
