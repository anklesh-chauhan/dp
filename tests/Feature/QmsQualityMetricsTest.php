<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Enums\ProductReturnStatus;
use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\EquipmentCalibration;
use App\Domain\QMS\Models\EquipmentMaintenance;
use App\Domain\QMS\Models\EquipmentQualification;
use App\Domain\QMS\Models\InternalAudit;
use App\Domain\QMS\Models\Investigation;
use App\Domain\QMS\Models\LaboratoryOosEvent;
use App\Domain\QMS\Models\ManagementReview;
use App\Domain\QMS\Models\ProductQualityReview;
use App\Domain\QMS\Models\ProductRecall;
use App\Domain\QMS\Models\ProductReturn;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\SupplierQualification;
use App\Domain\QMS\Models\ValidationMasterPlan;
use App\Domain\QMS\Services\QualityMetricsService;
use App\Exceptions\ModuleNotEnabledException;
use App\Filament\Pages\QualityMetrics;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
    ProductQualityReview::factory()->create([
        'status' => ProductQualityReviewStatus::InProgress,
        'period_end_at' => today()->subDay(),
    ]);
    LaboratoryOosEvent::factory()->create([
        'status' => LaboratoryOosStatus::PhaseOne,
        'started_at' => now()->subDay(),
    ]);
    ProductRecall::factory()->create([
        'status' => ProductRecallStatus::EffectivenessCheck,
    ]);
    ProductReturn::factory()->create([
        'status' => ProductReturnStatus::DispositionPending,
    ]);
    ValidationMasterPlan::factory()->create([
        'status' => ValidationMasterPlanStatus::Active,
        'period_end_at' => today()->subDay(),
    ]);
    EquipmentQualification::factory()->create([
        'status' => EquipmentQualificationStatus::InProgress,
        'started_at' => now()->subDay(),
    ]);
    EquipmentCalibration::factory()->create([
        'status' => EquipmentCalibrationStatus::Scheduled,
        'due_at' => today()->subDay(),
    ]);
    EquipmentMaintenance::factory()->create([
        'status' => EquipmentMaintenanceStatus::Planned,
        'due_at' => today()->subDay(),
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
        ->and($snapshot['lifecycles']['product_quality_reviews'][ProductQualityReviewStatus::InProgress->value])->toBe(1)
        ->and($snapshot['lifecycles']['product_recalls'][ProductRecallStatus::EffectivenessCheck->value])->toBe(1)
        ->and($snapshot['lifecycles']['product_returns'][ProductReturnStatus::DispositionPending->value])->toBe(1)
        ->and($snapshot['lifecycles']['laboratory_oos_events'][LaboratoryOosStatus::PhaseOne->value])->toBe(1)
        ->and($snapshot['lifecycles']['validation_master_plans'][ValidationMasterPlanStatus::Active->value])->toBe(1)
        ->and($snapshot['lifecycles']['equipment_qualifications'][EquipmentQualificationStatus::InProgress->value])->toBe(1)
        ->and($snapshot['lifecycles']['equipment_calibrations'][EquipmentCalibrationStatus::Scheduled->value])->toBe(1)
        ->and($snapshot['lifecycles']['equipment_maintenances'][EquipmentMaintenanceStatus::Planned->value])->toBe(1)
        ->and($snapshot['overdue']['deviations'])->toBe(1)
        ->and($snapshot['overdue']['investigations'])->toBe(1)
        ->and($snapshot['overdue']['capas'])->toBe(1)
        ->and($snapshot['overdue']['complaints'])->toBe(1)
        ->and($snapshot['overdue']['internal_audits'])->toBe(1)
        ->and($snapshot['overdue']['audit_findings'])->toBe(1)
        ->and($snapshot['overdue']['risk_assessments'])->toBe(1)
        ->and($snapshot['overdue']['supplier_qualifications'])->toBe(1)
        ->and($snapshot['overdue']['management_reviews'])->toBe(1)
        ->and($snapshot['overdue']['product_quality_reviews'])->toBe(1)
        ->and($snapshot['overdue']['product_recalls'])->toBe(1)
        ->and($snapshot['overdue']['product_returns'])->toBe(1)
        ->and($snapshot['overdue']['laboratory_oos_events'])->toBe(1)
        ->and($snapshot['overdue']['validation_master_plans'])->toBe(1)
        ->and($snapshot['overdue']['equipment_qualifications'])->toBe(1)
        ->and($snapshot['overdue']['equipment_calibrations'])->toBe(1)
        ->and($snapshot['overdue']['equipment_maintenances'])->toBe(1)
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

it('enforces metrics permission and QMS entitlement and exposes the Filament page', function (): void {
    expect(fn () => app(QualityMetricsService::class)->snapshot(User::factory()->create()))
        ->toThrow(AuthorizationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => app(QualityMetricsService::class)->snapshot($this->actor))
        ->toThrow(ModuleNotEnabledException::class)
        ->and(class_exists('App\\Filament\\Pages\\QualityMetrics'))
        ->toBeTrue();

    config()->set('modules.enabled', ['dms', 'qms']);
    $this->actingAs($this->actor);

    expect(QualityMetrics::canAccess())->toBeTrue();

    Livewire::test(QualityMetrics::class)
        ->assertSuccessful()
        ->assertSet('snapshot.overdue.deviations', 0);

    config()->set('modules.enabled', ['dms']);
    expect(QualityMetrics::canAccess())->toBeFalse();

    config()->set('modules.enabled', ['dms', 'qms']);
    $this->actingAs(User::factory()->create());
    expect(QualityMetrics::canAccess())->toBeFalse();
});
