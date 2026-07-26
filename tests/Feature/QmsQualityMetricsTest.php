<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\InternalAudit;
use App\Domain\QMS\Models\Investigation;
use App\Domain\QMS\Models\ManagementReview;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\SupplierQualification;
use App\Domain\QMS\Services\QualityMetricsService;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    Permission::findOrCreate('View:QualityMetrics', 'web');
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo('View:QualityMetrics');
});

it('reports aggregate lifecycle and overdue workload across every QMS aggregate', function (): void {
    $deviation = Deviation::factory()->create([
        'status' => DeviationStatus::UnderInvestigation,
        'investigation_due_at' => today()->subDay(),
        'title' => 'SENSITIVE-DEVIATION-TITLE',
    ]);
    Investigation::factory()->create([
        'deviation_id' => $deviation,
        'status' => InvestigationStatus::InProgress,
        'due_at' => today()->subDay(),
    ]);
    Capa::factory()->create([
        'deviation_id' => $deviation,
        'status' => CapaStatus::InProgress,
        'due_at' => today()->subDay(),
    ]);
    Complaint::factory()->create([
        'status' => ComplaintStatus::UnderAssessment,
        'response_due_at' => today()->subDay(),
    ]);
    $audit = InternalAudit::factory()->create([
        'status' => InternalAuditStatus::InProgress,
        'scheduled_end_at' => today()->subDay(),
    ]);
    AuditFinding::factory()->create([
        'internal_audit_id' => $audit,
        'disposition' => AuditFindingDisposition::ResponsePending,
        'response_due_at' => today()->subDay(),
    ]);
    RiskAssessment::factory()->create([
        'status' => RiskAssessmentStatus::MitigationInProgress,
        'mitigation_due_at' => today()->subDay(),
    ]);
    SupplierQualification::factory()->create([
        'status' => SupplierQualificationStatus::Qualified,
        'next_review_at' => today()->subDay(),
    ]);
    ManagementReview::factory()->create([
        'status' => ManagementReviewStatus::Scheduled,
        'scheduled_at' => now()->subDay(),
    ]);

    $snapshot = app(QualityMetricsService::class)->snapshot($this->actor);

    expect($snapshot['lifecycles']['deviations'][DeviationStatus::UnderInvestigation->value])->toBe(1)
        ->and($snapshot['lifecycles']['investigations'][InvestigationStatus::InProgress->value])->toBe(1)
        ->and($snapshot['lifecycles']['capas'][CapaStatus::InProgress->value])->toBe(1)
        ->and($snapshot['lifecycles']['complaints'][ComplaintStatus::UnderAssessment->value])->toBe(1)
        ->and($snapshot['lifecycles']['internal_audits'][InternalAuditStatus::InProgress->value])->toBe(1)
        ->and($snapshot['lifecycles']['audit_findings'][AuditFindingDisposition::ResponsePending->value])->toBe(1)
        ->and($snapshot['lifecycles']['risk_assessments'][RiskAssessmentStatus::MitigationInProgress->value])->toBe(1)
        ->and($snapshot['lifecycles']['supplier_qualifications'][SupplierQualificationStatus::Qualified->value])->toBe(1)
        ->and($snapshot['lifecycles']['management_reviews'][ManagementReviewStatus::Scheduled->value])->toBe(1)
        ->and($snapshot['overdue'])->toBe([
            'deviations' => 1,
            'investigations' => 1,
            'capas' => 1,
            'complaints' => 1,
            'internal_audits' => 1,
            'audit_findings' => 1,
            'risk_assessments' => 1,
            'supplier_qualifications' => 1,
            'management_reviews' => 1,
        ])
        ->and(json_encode($snapshot))->not->toContain('SENSITIVE-DEVIATION-TITLE');
});

it('excludes terminal records from overdue workload', function (): void {
    Complaint::factory()->create([
        'status' => ComplaintStatus::Closed,
        'response_due_at' => today()->subMonth(),
    ]);
    ManagementReview::factory()->create([
        'status' => ManagementReviewStatus::Completed,
        'scheduled_at' => now()->subMonth(),
    ]);

    $snapshot = app(QualityMetricsService::class)->snapshot($this->actor);

    expect($snapshot['overdue']['complaints'])->toBe(0)
        ->and($snapshot['overdue']['management_reviews'])->toBe(0);
});

it('enforces metrics permission and QMS entitlement without exposing UI', function (): void {
    expect(fn () => app(QualityMetricsService::class)->snapshot(User::factory()->create()))
        ->toThrow(AuthorizationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => app(QualityMetricsService::class)->snapshot($this->actor))
        ->toThrow(ModuleNotEnabledException::class)
        ->and(class_exists('App\\Filament\\Pages\\QualityMetrics'))
        ->toBeFalse();
});
