<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\AuditFindingSeverity;
use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\InternalAudit;
use App\Domain\QMS\Models\ManagementReview;
use App\Domain\QMS\Models\ProductQualityReview;
use App\Domain\QMS\Models\ProductRecall;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Models\SupplierQualification;
use App\Domain\QMS\Services\ManagementReviewInputAssembler;
use App\Domain\QMS\Services\ManagementReviewPackService;
use App\Domain\QMS\Services\QualityMetricsService;
use App\Exceptions\ModuleNotEnabledException;
use App\Filament\Pages\ScheduleMReadiness;
use App\Filament\Resources\ManagementReviews\Pages\ViewManagementReview;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach ([
        'View:QualityMetrics',
        'View:ManagementReview',
        'ViewAny:ManagementReview',
        'Update:ManagementReview',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo([
        'View:QualityMetrics',
        'View:ManagementReview',
        'ViewAny:ManagementReview',
        'Update:ManagementReview',
    ]);
});

it('assembles management review input sections from live aggregates without PII', function (): void {
    $review = ManagementReview::factory()->create([
        'status' => ManagementReviewStatus::Draft,
        'title' => 'SENSITIVE-MR-TITLE',
    ]);

    $deviation = Deviation::factory()->create([
        'status' => DeviationStatus::Open,
        'investigation_due_at' => today()->subDay(),
        'title' => 'SENSITIVE-DEVIATION-TITLE',
    ]);
    Capa::factory()->create([
        'deviation_id' => $deviation,
        'status' => CapaStatus::InProgress,
        'due_at' => today()->subDay(),
        'title' => 'SENSITIVE-CAPA-TITLE',
    ]);
    InternalAudit::factory()->create([
        'status' => InternalAuditStatus::InProgress,
        'title' => 'SENSITIVE-AUDIT-TITLE',
    ]);
    AuditFinding::factory()->create([
        'severity' => AuditFindingSeverity::Critical,
        'disposition' => AuditFindingDisposition::Open,
        'title' => 'SENSITIVE-FINDING-TITLE',
    ]);
    ProductQualityReview::factory()->create([
        'status' => ProductQualityReviewStatus::InProgress,
        'title' => 'SENSITIVE-PQR-TITLE',
    ]);
    ProductRecall::factory()->create([
        'status' => ProductRecallStatus::Initiated,
        'title' => 'SENSITIVE-RECALL-TITLE',
    ]);
    SupplierQualification::factory()->create([
        'status' => SupplierQualificationStatus::Qualified,
        'legal_name' => 'SENSITIVE-SUPPLIER-NAME',
        'qualification_expires_at' => today()->addDays(30),
    ]);
    RiskAssessment::factory()->create([
        'status' => RiskAssessmentStatus::Monitoring,
        'review_due_at' => today()->subDay(),
        'title' => 'SENSITIVE-RISK-TITLE',
    ]);

    $sections = app(ManagementReviewInputAssembler::class)->assemble($review, $this->actor);

    expect($sections)->not->toBeEmpty()
        ->and(collect($sections)->pluck('title')->all())->toContain(
            'Deviations and CAPAs',
            'Internal audits and findings',
            'Product quality reviews',
            'Product recalls',
            'Supplier qualifications',
            'Risk assessments',
        );

    $deviationSection = collect($sections)->firstWhere('title', 'Deviations and CAPAs');
    expect($deviationSection['stats']['open_deviations'])->toBe(1)
        ->and($deviationSection['stats']['overdue_deviations'])->toBe(1)
        ->and($deviationSection['stats']['open_capas'])->toBe(1)
        ->and($deviationSection['stats']['overdue_capas'])->toBe(1);

    $auditSection = collect($sections)->firstWhere('title', 'Internal audits and findings');
    expect($auditSection['stats']['open_critical_findings'])->toBe(1);

    $supplierSection = collect($sections)->firstWhere('title', 'Supplier qualifications');
    expect($supplierSection['stats']['expiring_within_90_days'])->toBe(1);

    $riskSection = collect($sections)->firstWhere('title', 'Risk assessments');
    expect($riskSection['stats']['monitoring_past_review_due'])->toBe(1);

    $encoded = json_encode($sections);
    expect($encoded)->not->toContain('SENSITIVE-DEVIATION-TITLE')
        ->and($encoded)->not->toContain('SENSITIVE-CAPA-TITLE')
        ->and($encoded)->not->toContain('SENSITIVE-FINDING-TITLE')
        ->and($encoded)->not->toContain('SENSITIVE-SUPPLIER-NAME')
        ->and($encoded)->not->toContain('SENSITIVE-RISK-TITLE');
});

it('builds a printable management review pack with assembled inputs and attachment count', function (): void {
    $review = ManagementReview::factory()->create([
        'status' => ManagementReviewStatus::Scheduled,
        'input_summary' => 'Existing summary',
    ]);

    $pack = app(ManagementReviewPackService::class)->build($review, $this->actor);

    expect($pack)->toHaveKeys([
        'generated_at',
        'review',
        'assembled_inputs',
        'assembled_inputs_markdown',
        'attachment_count',
    ])
        ->and($pack['review']['review_number'])->toBe($review->review_number)
        ->and($pack['review']['input_summary'])->toBe('Existing summary')
        ->and($pack['assembled_inputs'])->not->toBeEmpty()
        ->and($pack['assembled_inputs_markdown'])->toContain('Suggested management review inputs')
        ->and($pack['attachment_count'])->toBe(0);
});

it('exposes schedule m readiness aggregates including gap compliance percent', function (): void {
    $assessment = ScheduleMGapAssessment::factory()->withPartIChecklist()->create([
        'status' => ScheduleMGapAssessmentStatus::InProgress,
    ]);
    $assessment->items()->update(['status' => ScheduleMGapItemStatus::Compliant->value]);
    $assessment->items()->first()?->update(['status' => ScheduleMGapItemStatus::Gap->value]);

    Deviation::factory()->create(['status' => DeviationStatus::Open]);
    AuditFinding::factory()->create([
        'severity' => AuditFindingSeverity::Critical,
        'disposition' => AuditFindingDisposition::ResponsePending,
    ]);

    $readiness = app(QualityMetricsService::class)->scheduleMReadiness($this->actor);

    expect($readiness['gap_assessment'])->not->toBeNull()
        ->and($readiness['gap_assessment']['assessment_number'])->toBe($assessment->assessment_number)
        ->and($readiness['gap_assessment']['total_items'])->toBe(22)
        ->and($readiness['gap_assessment']['compliant_items'])->toBe(21)
        ->and($readiness['gap_assessment']['percent_compliant'])->toBe(95)
        ->and($readiness['overdue_calibrations'])->toBe(0)
        ->and($readiness['open_critical_audit_findings'])->toBe(1)
        ->and($readiness['open_deviations'])->toBe(1)
        ->and($readiness['open_capas'])->toBe(0);
});

it('gates assembler, pack, and readiness on metrics permission and qms entitlement', function (): void {
    $review = ManagementReview::factory()->create();
    $assembler = app(ManagementReviewInputAssembler::class);
    $pack = app(ManagementReviewPackService::class);
    $metrics = app(QualityMetricsService::class);

    expect(fn () => $assembler->assemble($review, User::factory()->create()))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $pack->build($review, User::factory()->create()))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $metrics->scheduleMReadiness(User::factory()->create()))
        ->toThrow(AuthorizationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $assembler->assemble($review, $this->actor))
        ->toThrow(ModuleNotEnabledException::class)
        ->and(fn () => $pack->build($review, $this->actor))
        ->toThrow(ModuleNotEnabledException::class)
        ->and(fn () => $metrics->scheduleMReadiness($this->actor))
        ->toThrow(ModuleNotEnabledException::class);
});

it('allows schedule m readiness page access with View:QualityMetrics when qms is enabled', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    $this->actingAs($this->actor);

    expect(ScheduleMReadiness::canAccess())->toBeTrue();

    Livewire::test(ScheduleMReadiness::class)
        ->assertSuccessful()
        ->assertSet('readiness.open_deviations', 0)
        ->assertSet('readiness.overdue_calibrations', 0);

    config()->set('modules.enabled', ['dms']);
    expect(ScheduleMReadiness::canAccess())->toBeFalse();

    config()->set('modules.enabled', ['dms', 'qms']);
    $this->actingAs(User::factory()->create());
    expect(ScheduleMReadiness::canAccess())->toBeFalse();
});

it('applies assembled suggested inputs to draft management review input_summary', function (): void {
    $this->actingAs($this->actor);

    $review = ManagementReview::factory()->create([
        'status' => ManagementReviewStatus::Draft,
        'input_summary' => null,
    ]);

    Livewire::test(ViewManagementReview::class, ['record' => $review->id])
        ->callAction('assembleSuggestedInputs', [
            'preview' => "# Suggested management review inputs\n\n## Deviations and CAPAs\nOpen deviations: 0.\n",
            'apply_to_input_summary' => true,
        ])
        ->assertNotified();

    expect($review->fresh()?->input_summary)->toContain('Suggested management review inputs');
});
