<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Enums\ChangeImpactClassification;
use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\Investigation;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\RiskAssessmentLink;
use App\Domain\QMS\Services\ChangeControlTransitionService;
use App\Domain\QMS\Services\DeviationTransitionService;
use App\Domain\QMS\Services\QualityMetricsService;
use App\Domain\QMS\Services\RiskAssessmentLinkService;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach ([
        'Update:RiskAssessment',
        'View:RiskAssessment',
        'View:QualityMetrics',
        'Submit:ChangeControl',
        'Review:ChangeControl',
        'Approve:ChangeControl',
        'Submit:Deviation',
        'Investigate:Deviation',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo([
        'Update:RiskAssessment',
        'View:RiskAssessment',
        'View:QualityMetrics',
        'Submit:ChangeControl',
        'Review:ChangeControl',
        'Approve:ChangeControl',
        'Submit:Deviation',
        'Investigate:Deviation',
    ]);
});

it('attaches and detaches polymorphic risk assessment links', function (): void {
    $assessment = RiskAssessment::factory()->create();
    $change = ChangeControl::factory()->create();
    $deviation = Deviation::factory()->create();

    $service = app(RiskAssessmentLinkService::class);

    $changeLink = $service->attach($assessment, $change, $this->actor, 'source');
    $deviationLink = $service->attach($assessment, $deviation, $this->actor, 'mitigation_context');

    expect($changeLink)->toBeInstanceOf(RiskAssessmentLink::class)
        ->and($assessment->fresh()->changeControls)->toHaveCount(1)
        ->and($assessment->fresh()->deviations)->toHaveCount(1)
        ->and($change->fresh()->riskAssessments)->toHaveCount(1)
        ->and($deviation->fresh()->riskAssessments->first()?->is($assessment))->toBeTrue();

    expect(fn () => $service->attach($assessment, $change, $this->actor))
        ->toThrow(ValidationException::class);

    $service->detach($deviationLink, $this->actor);

    expect($assessment->fresh()->deviations)->toHaveCount(0)
        ->and(RiskAssessmentLink::query()->count())->toBe(1);
});

it('rejects link mutations without QMS entitlement or update permission', function (): void {
    $assessment = RiskAssessment::factory()->create();
    $change = ChangeControl::factory()->create();

    config()->set('modules.enabled', ['dms']);

    expect(fn () => app(RiskAssessmentLinkService::class)->attach($assessment, $change, $this->actor))
        ->toThrow(ModuleNotEnabledException::class);

    config()->set('modules.enabled', ['dms', 'qms']);

    $unauthorized = User::factory()->create();

    expect(fn () => app(RiskAssessmentLinkService::class)->attach($assessment, $change, $unauthorized))
        ->toThrow(AuthorizationException::class);
});

it('blocks major change approval without an accepted linked risk assessment', function (): void {
    $change = ChangeControl::factory()->create([
        'impact_classification' => ChangeImpactClassification::Major,
        'status' => ChangeControlStatus::Draft,
    ]);

    $service = app(ChangeControlTransitionService::class);
    $service->transition($change, ChangeControlStatus::Submitted, $this->actor, 'Submit');
    $service->transition($change->fresh(), ChangeControlStatus::UnderReview, $this->actor);

    expect(fn () => $service->transition(
        $change->fresh(),
        ChangeControlStatus::Approved,
        $this->actor,
        'Approve without risk',
    ))->toThrow(ValidationException::class)
        ->and($change->fresh()->status)->toBe(ChangeControlStatus::UnderReview);
});

it('allows major change approval when a linked accepted risk assessment exists', function (): void {
    $change = ChangeControl::factory()->create([
        'impact_classification' => ChangeImpactClassification::Major,
        'status' => ChangeControlStatus::Draft,
    ]);
    $assessment = RiskAssessment::factory()->create([
        'status' => RiskAssessmentStatus::Approved,
        'initial_severity' => 3,
        'initial_probability' => 3,
        'initial_detectability' => 3,
        'residual_severity' => 2,
        'residual_probability' => 2,
        'residual_detectability' => 2,
    ]);

    app(RiskAssessmentLinkService::class)->attach($assessment, $change, $this->actor, 'source');

    $service = app(ChangeControlTransitionService::class);
    $service->transition($change, ChangeControlStatus::Submitted, $this->actor, 'Submit');
    $service->transition($change->fresh(), ChangeControlStatus::UnderReview, $this->actor);
    $approved = $service->transition(
        $change->fresh(),
        ChangeControlStatus::Approved,
        $this->actor,
        'Approve with accepted residual risk',
    );

    expect($approved->status)->toBe(ChangeControlStatus::Approved);
});

it('blocks critical deviation investigation completion without a linked risk assessment', function (): void {
    $deviation = Deviation::factory()->create([
        'severity' => DeviationSeverity::Critical,
        'status' => DeviationStatus::Draft,
    ]);
    Investigation::factory()->create([
        'deviation_id' => $deviation,
        'status' => InvestigationStatus::Completed,
        'root_cause' => 'Documented root cause',
        'conclusion' => 'Documented conclusion',
        'completed_at' => now(),
    ]);

    $service = app(DeviationTransitionService::class);
    $service->transition($deviation, DeviationStatus::Open, $this->actor, 'Open');
    $service->transition($deviation->fresh(), DeviationStatus::UnderInvestigation, $this->actor, 'Investigate');

    expect(fn () => $service->transition(
        $deviation->fresh(),
        DeviationStatus::InvestigationComplete,
        $this->actor,
        'Complete without risk',
    ))->toThrow(ValidationException::class);

    $assessment = RiskAssessment::factory()->create();
    app(RiskAssessmentLinkService::class)->attach($assessment, $deviation->fresh(), $this->actor);

    $completed = $service->transition(
        $deviation->fresh(),
        DeviationStatus::InvestigationComplete,
        $this->actor,
        'Complete with linked risk',
    );

    expect($completed->status)->toBe(DeviationStatus::InvestigationComplete);
});

it('includes overdue monitoring risk reviews in quality metrics', function (): void {
    RiskAssessment::factory()->create([
        'status' => RiskAssessmentStatus::Monitoring,
        'review_due_at' => today()->subDay(),
        'mitigation_due_at' => today()->addWeek(),
        'residual_severity' => 2,
        'residual_probability' => 2,
        'residual_detectability' => 2,
    ]);

    $snapshot = app(QualityMetricsService::class)->snapshot($this->actor);

    expect($snapshot['overdue']['risk_assessments'])->toBe(1);
});
