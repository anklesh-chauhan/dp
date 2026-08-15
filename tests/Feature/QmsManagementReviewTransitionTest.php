<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ManagementReviewInputType;
use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Models\ManagementReview;
use App\Domain\QMS\Models\ManagementReviewEvent;
use App\Domain\QMS\Services\ManagementReviewTransitionService;
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
        'Schedule:ManagementReview',
        'Conduct:ManagementReview',
        'IssueMinutes:ManagementReview',
        'Approve:ManagementReview',
        'Complete:ManagementReview',
        'Manage:ManagementReview',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->review = ManagementReview::factory()->create();
});

it('records scheduling conduct minutes actions approval and completion with signed history', function (): void {
    $service = app(ManagementReviewTransitionService::class);
    $service->transition($this->review, ManagementReviewStatus::Scheduled, $this->actor, 'Agenda and attendees confirmed.');
    $service->transition($this->review, ManagementReviewStatus::InProgress, $this->actor, 'Meeting opened.');
    $minutes = $service->transition(
        $this->review,
        ManagementReviewStatus::MinutesPending,
        $this->actor,
        'All required inputs reviewed and decisions recorded.',
        inputSummary: 'Quality metrics, audits, complaints, CAPA, suppliers, risks, resources, and changes were reviewed.',
        decisions: 'Increase data-integrity audit sampling and accelerate supplier requalification.',
        ipAddress: '203.0.113.91',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );
    $actions = $service->transition(
        $minutes,
        ManagementReviewStatus::ActionsPending,
        $this->actor,
        'Controlled minutes issued.',
        actionSummary: 'Quality Systems and Supplier Quality own the approved follow-up actions.',
    );
    $completed = $service->transition(
        $actions,
        ManagementReviewStatus::Completed,
        $this->actor,
        'Minutes and actions independently approved.',
    );

    $events = $completed->auditEvents()->orderBy('id')->get();
    $minutesEvent = $events->get(2);

    expect($completed->status)->toBe(ManagementReviewStatus::Completed)
        ->and($completed->held_at)->not->toBeNull()
        ->and($completed->minutes_issued_at)->not->toBeNull()
        ->and($completed->approved_by)->toBe($this->actor->id)
        ->and($completed->approved_at)->not->toBeNull()
        ->and($completed->completed_at)->not->toBeNull()
        ->and($events)->toHaveCount(5)
        ->and($events->first()->signature_hash)->toBeNull()
        ->and($minutesEvent?->signatureMeaning())->toBe(ManagementReviewStatus::MinutesPending->value)
        ->and($minutesEvent?->signatureIpAddress())->toBe('203.0.113.91')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($minutesEvent))->toBeTrue();

    expect(fn () => $minutesEvent?->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('requires coherent dates complete inputs decisions actions and independent approval', function (): void {
    $service = app(ManagementReviewTransitionService::class);
    $this->review->update([
        'required_inputs' => [ManagementReviewInputType::AuditResults->value],
    ]);

    expect(fn () => $service->transition(
        $this->review,
        ManagementReviewStatus::Scheduled,
        $this->actor,
        'Incomplete inputs.',
    ))->toThrow(ValidationException::class);

    $this->review->update([
        'status' => ManagementReviewStatus::InProgress,
        'required_inputs' => array_map(
            static fn (ManagementReviewInputType $type): string => $type->value,
            ManagementReviewInputType::cases(),
        ),
    ]);

    expect(fn () => $service->transition(
        $this->review,
        ManagementReviewStatus::MinutesPending,
        $this->actor,
        'Missing outputs.',
    ))->toThrow(ValidationException::class);

    $this->review->update([
        'status' => ManagementReviewStatus::ActionsPending,
        'input_summary' => 'Inputs reviewed.',
        'decisions' => 'Decision recorded.',
        'action_summary' => 'Actions assigned.',
        'minutes_issued_at' => now(),
        'coordinator_id' => $this->actor->id,
    ]);

    expect(fn () => $service->transition(
        $this->review,
        ManagementReviewStatus::Completed,
        $this->actor,
        'Coordinator self-approval.',
    ))->toThrow(ValidationException::class)
        ->and(ManagementReviewEvent::query()->count())->toBe(0);
});

it('requires both completion and approval permissions and module entitlement', function (): void {
    $service = app(ManagementReviewTransitionService::class);
    $this->review->update([
        'status' => ManagementReviewStatus::ActionsPending,
        'input_summary' => 'Inputs reviewed.',
        'decisions' => 'Decision recorded.',
        'action_summary' => 'Actions assigned.',
        'minutes_issued_at' => now(),
    ]);
    $completionOnly = User::factory()->create();
    $completionOnly->givePermissionTo('Complete:ManagementReview');

    expect(fn () => $service->transition(
        $this->review,
        ManagementReviewStatus::Completed,
        $completionOnly,
        'Missing approval permission.',
    ))->toThrow(AuthorizationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->review,
        ManagementReviewStatus::Completed,
        $this->actor,
        'Disabled.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(ManagementReviewEvent::query()->count())->toBe(0);
});
