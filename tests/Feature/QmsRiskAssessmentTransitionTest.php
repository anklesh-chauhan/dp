<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\RiskAssessmentEvent;
use App\Domain\QMS\Services\RiskAssessmentTransitionService;
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
        'Review:RiskAssessment',
        'Approve:RiskAssessment',
        'Mitigate:RiskAssessment',
        'Monitor:RiskAssessment',
        'Close:RiskAssessment',
        'Manage:RiskAssessment',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->assessment = RiskAssessment::factory()->create([
        'initial_severity' => 5,
        'initial_probability' => 4,
        'initial_detectability' => 3,
    ]);
});

it('records approval mitigation residual monitoring and closure with signed history', function (): void {
    $service = app(RiskAssessmentTransitionService::class);
    $service->transition($this->assessment, RiskAssessmentStatus::InReview, $this->actor, 'Assessment ready for review.');
    $approved = $service->transition(
        $this->assessment,
        RiskAssessmentStatus::Approved,
        $this->actor,
        'Initial risk assessment approved.',
        ipAddress: '203.0.113.71',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );
    $service->transition(
        $approved,
        RiskAssessmentStatus::MitigationInProgress,
        $this->actor,
        'Mitigation authorized.',
        mitigationPlan: 'Install technical controls and retrain operators.',
    );
    $monitoring = $service->transition(
        $this->assessment,
        RiskAssessmentStatus::Monitoring,
        $this->actor,
        'Mitigation completed and residual risk assessed.',
        residualScores: ['severity' => 4, 'probability' => 2, 'detectability' => 1],
    );
    $closed = $service->transition(
        $monitoring,
        RiskAssessmentStatus::Closed,
        $this->actor,
        'Residual risk accepted with scheduled review.',
    );

    $events = $closed->auditEvents()->orderBy('id')->get();
    $approvalEvent = $events->get(1);

    expect($closed->status)->toBe(RiskAssessmentStatus::Closed)
        ->and($closed->approver_id)->toBe($this->actor->id)
        ->and($closed->approved_at)->not->toBeNull()
        ->and($closed->mitigation_completed_at)->not->toBeNull()
        ->and($closed->residualRiskPriorityNumber())->toBe(8)
        ->and($closed->closed_at)->not->toBeNull()
        ->and($events)->toHaveCount(5)
        ->and($events->first()->signature_hash)->toBeNull()
        ->and($approvalEvent?->signatureMeaning())->toBe(RiskAssessmentStatus::Approved->value)
        ->and($approvalEvent?->signatureIpAddress())->toBe('203.0.113.71')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($approvalEvent))->toBeTrue();

    expect(fn () => $approvalEvent?->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('validates scoring bounds mitigation evidence and residual closure risk', function (): void {
    $service = app(RiskAssessmentTransitionService::class);
    $this->assessment->update(['initial_severity' => 6]);

    expect(fn () => $service->transition(
        $this->assessment,
        RiskAssessmentStatus::InReview,
        $this->actor,
        'Invalid score.',
    ))->toThrow(ValidationException::class);

    $this->assessment->update([
        'status' => RiskAssessmentStatus::Approved,
        'initial_severity' => 2,
        'initial_probability' => 2,
        'initial_detectability' => 2,
        'mitigation_plan' => null,
    ]);

    expect(fn () => $service->transition(
        $this->assessment,
        RiskAssessmentStatus::MitigationInProgress,
        $this->actor,
        'Missing mitigation.',
    ))->toThrow(ValidationException::class);

    $this->assessment->update([
        'status' => RiskAssessmentStatus::Monitoring,
        'residual_severity' => 5,
        'residual_probability' => 5,
        'residual_detectability' => 5,
    ]);

    expect(fn () => $service->transition(
        $this->assessment,
        RiskAssessmentStatus::Closed,
        $this->actor,
        'Residual risk increased.',
    ))->toThrow(ValidationException::class)
        ->and(RiskAssessmentEvent::query()->count())->toBe(0);
});

it('enforces independent approval authorization and module entitlement', function (): void {
    $service = app(RiskAssessmentTransitionService::class);
    $this->assessment->update([
        'status' => RiskAssessmentStatus::InReview,
        'owner_id' => $this->actor->id,
    ]);

    expect(fn () => $service->transition(
        $this->assessment,
        RiskAssessmentStatus::Approved,
        $this->actor,
        'Owner self-approval.',
    ))->toThrow(ValidationException::class);

    $unauthorized = User::factory()->create();
    expect(fn () => $service->transition(
        $this->assessment,
        RiskAssessmentStatus::Approved,
        $unauthorized,
        'Unauthorized.',
    ))->toThrow(AuthorizationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->assessment,
        RiskAssessmentStatus::Approved,
        $this->actor,
        'Disabled.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(RiskAssessmentEvent::query()->count())->toBe(0);
});
