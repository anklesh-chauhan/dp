<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\AuditFindingSeverity;
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
use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Enums\SiteMasterFileStatus;
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
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Models\SiteMasterFile;
use App\Domain\QMS\Models\SupplierQualification;
use App\Domain\QMS\Models\ValidationMasterPlan;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

final class QualityMetricsService
{
    public function __construct(private readonly ModuleManager $moduleManager) {}

    /**
     * @return array{
     *     lifecycles: array<string, array<string, int>>,
     *     overdue: array<string, int>
     * }
     */
    public function snapshot(User $actor): array
    {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('View:QualityMetrics')) {
            throw new AuthorizationException('You do not have permission to view quality metrics.');
        }

        return [
            'lifecycles' => [
                'deviations' => $this->statusCounts(Deviation::class),
                'investigations' => $this->statusCounts(Investigation::class),
                'capas' => $this->statusCounts(Capa::class),
                'complaints' => $this->statusCounts(Complaint::class),
                'internal_audits' => $this->statusCounts(InternalAudit::class),
                'audit_findings' => $this->statusCounts(AuditFinding::class, 'disposition'),
                'risk_assessments' => $this->statusCounts(RiskAssessment::class),
                'supplier_qualifications' => $this->statusCounts(SupplierQualification::class),
                'management_reviews' => $this->statusCounts(ManagementReview::class),
                'product_quality_reviews' => $this->statusCounts(ProductQualityReview::class),
                'product_recalls' => $this->statusCounts(ProductRecall::class),
                'product_returns' => $this->statusCounts(ProductReturn::class),
                'laboratory_oos_events' => $this->statusCounts(LaboratoryOosEvent::class),
                'validation_master_plans' => $this->statusCounts(ValidationMasterPlan::class),
                'equipment_qualifications' => $this->statusCounts(EquipmentQualification::class),
                'equipment_calibrations' => $this->statusCounts(EquipmentCalibration::class),
                'equipment_maintenances' => $this->statusCounts(EquipmentMaintenance::class),
                'schedule_m_gap_assessments' => $this->statusCounts(ScheduleMGapAssessment::class),
                'site_master_files' => $this->statusCounts(SiteMasterFile::class),
            ],
            'overdue' => [
                'deviations' => Deviation::query()
                    ->whereDate('investigation_due_at', '<', today())
                    ->whereNotIn('status', [
                        DeviationStatus::Closed->value,
                        DeviationStatus::Rejected->value,
                        DeviationStatus::Cancelled->value,
                    ])->count(),
                'investigations' => Investigation::query()
                    ->whereDate('due_at', '<', today())
                    ->whereNotIn('status', [
                        InvestigationStatus::Completed->value,
                        InvestigationStatus::Cancelled->value,
                    ])->count(),
                'capas' => Capa::query()
                    ->whereDate('due_at', '<', today())
                    ->whereNotIn('status', [
                        CapaStatus::Effective->value,
                        CapaStatus::Closed->value,
                        CapaStatus::Cancelled->value,
                    ])->count(),
                'complaints' => Complaint::query()
                    ->whereDate('response_due_at', '<', today())
                    ->whereNotIn('status', [
                        ComplaintStatus::Closed->value,
                        ComplaintStatus::Rejected->value,
                        ComplaintStatus::Cancelled->value,
                    ])->count(),
                'internal_audits' => InternalAudit::query()
                    ->whereDate('scheduled_end_at', '<', today())
                    ->whereNotIn('status', [
                        InternalAuditStatus::Closed->value,
                        InternalAuditStatus::Cancelled->value,
                    ])->count(),
                'audit_findings' => AuditFinding::query()
                    ->whereDate('response_due_at', '<', today())
                    ->whereNotIn('disposition', [
                        AuditFindingDisposition::Closed->value,
                        AuditFindingDisposition::Rejected->value,
                        AuditFindingDisposition::Cancelled->value,
                    ])->count(),
                'risk_assessments' => RiskAssessment::query()
                    ->where(function ($query): void {
                        $query->where(function ($mitigation): void {
                            $mitigation->whereDate('mitigation_due_at', '<', today())
                                ->whereIn('status', [
                                    RiskAssessmentStatus::Approved->value,
                                    RiskAssessmentStatus::MitigationInProgress->value,
                                ]);
                        })->orWhere(function ($review): void {
                            $review->whereDate('review_due_at', '<', today())
                                ->where('status', RiskAssessmentStatus::Monitoring->value);
                        });
                    })
                    ->count(),
                'supplier_qualifications' => SupplierQualification::query()
                    ->whereDate('next_review_at', '<', today())
                    ->whereIn('status', [
                        SupplierQualificationStatus::Qualified->value,
                        SupplierQualificationStatus::ConditionallyQualified->value,
                    ])->count(),
                'management_reviews' => ManagementReview::query()
                    ->where('scheduled_at', '<', now())
                    ->whereNotIn('status', [
                        ManagementReviewStatus::Completed->value,
                        ManagementReviewStatus::Cancelled->value,
                    ])->count(),
                'product_quality_reviews' => ProductQualityReview::query()
                    ->where('status', ProductQualityReviewStatus::InProgress->value)
                    ->whereDate('period_end_at', '<', today())
                    ->count(),
                'product_recalls' => ProductRecall::query()
                    ->where('status', ProductRecallStatus::EffectivenessCheck->value)
                    ->count(),
                'product_returns' => ProductReturn::query()
                    ->where('status', ProductReturnStatus::DispositionPending->value)
                    ->count(),
                'laboratory_oos_events' => LaboratoryOosEvent::query()
                    ->whereDate('started_at', '<', today())
                    ->whereNotIn('status', [
                        LaboratoryOosStatus::Closed->value,
                        LaboratoryOosStatus::Cancelled->value,
                    ])->count(),
                'validation_master_plans' => ValidationMasterPlan::query()
                    ->where('status', ValidationMasterPlanStatus::Active->value)
                    ->whereDate('period_end_at', '<', today())
                    ->count(),
                'equipment_qualifications' => EquipmentQualification::query()
                    ->whereDate('started_at', '<', today())
                    ->whereIn('status', [
                        EquipmentQualificationStatus::InProgress->value,
                        EquipmentQualificationStatus::UnderReview->value,
                    ])->count(),
                'equipment_calibrations' => EquipmentCalibration::query()
                    ->whereDate('due_at', '<', today())
                    ->whereIn('status', [
                        EquipmentCalibrationStatus::Scheduled->value,
                        EquipmentCalibrationStatus::InProgress->value,
                    ])->count(),
                'equipment_maintenances' => EquipmentMaintenance::query()
                    ->whereDate('due_at', '<', today())
                    ->whereIn('status', [
                        EquipmentMaintenanceStatus::Planned->value,
                        EquipmentMaintenanceStatus::InProgress->value,
                    ])->count(),
                'schedule_m_gap_assessments' => ScheduleMGapAssessment::query()
                    ->whereIn('status', [
                        ScheduleMGapAssessmentStatus::InProgress->value,
                        ScheduleMGapAssessmentStatus::UnderReview->value,
                    ])
                    ->whereHas('items', function ($query): void {
                        $query->whereDate('due_at', '<', today())
                            ->whereNull('closed_at');
                    })
                    ->count(),
                'site_master_files' => SiteMasterFile::query()
                    ->where('status', SiteMasterFileStatus::InReview->value)
                    ->count(),
            ],
        ];
    }

    /**
     * Aggregate Schedule M PQS readiness indicators (integer aggregates only).
     *
     * @return array{
     *     gap_assessment: array{
     *         assessment_number: string|null,
     *         status: string|null,
     *         total_items: int,
     *         compliant_items: int,
     *         percent_compliant: int
     *     }|null,
     *     overdue_calibrations: int|null,
     *     open_critical_audit_findings: int,
     *     open_deviations: int,
     *     overdue_deviations: int,
     *     open_capas: int,
     *     overdue_capas: int
     * }
     */
    public function scheduleMReadiness(User $actor): array
    {
        $snapshot = $this->snapshot($actor);

        return [
            'gap_assessment' => $this->latestGapAssessmentCompliance(),
            'overdue_calibrations' => $this->overdueCalibrationCount(),
            'open_critical_audit_findings' => AuditFinding::query()
                ->where('severity', AuditFindingSeverity::Critical->value)
                ->whereNotIn('disposition', [
                    AuditFindingDisposition::Closed->value,
                    AuditFindingDisposition::Rejected->value,
                    AuditFindingDisposition::Cancelled->value,
                ])
                ->count(),
            'open_deviations' => Deviation::query()
                ->whereNotIn('status', [
                    DeviationStatus::Closed->value,
                    DeviationStatus::Rejected->value,
                    DeviationStatus::Cancelled->value,
                ])
                ->count(),
            'overdue_deviations' => (int) ($snapshot['overdue']['deviations'] ?? 0),
            'open_capas' => Capa::query()
                ->whereNotIn('status', [
                    CapaStatus::Effective->value,
                    CapaStatus::Closed->value,
                    CapaStatus::Cancelled->value,
                ])
                ->count(),
            'overdue_capas' => (int) ($snapshot['overdue']['capas'] ?? 0),
        ];
    }

    /**
     * @return array{
     *     assessment_number: string|null,
     *     status: string|null,
     *     total_items: int,
     *     compliant_items: int,
     *     percent_compliant: int
     * }|null
     */
    private function latestGapAssessmentCompliance(): ?array
    {
        $assessment = ScheduleMGapAssessment::query()
            ->whereIn('status', [
                ScheduleMGapAssessmentStatus::InProgress->value,
                ScheduleMGapAssessmentStatus::UnderReview->value,
                ScheduleMGapAssessmentStatus::Approved->value,
            ])
            ->latest('id')
            ->first();

        if ($assessment === null) {
            return null;
        }

        $assessment->loadMissing('items');
        $total = $assessment->items->count();
        $compliant = $assessment->items
            ->filter(static fn ($item): bool => $item->status === ScheduleMGapItemStatus::Compliant
                || $item->status === ScheduleMGapItemStatus::Compliant->value)
            ->count();

        return [
            'assessment_number' => $assessment->assessment_number,
            'status' => $assessment->status->value,
            'total_items' => $total,
            'compliant_items' => $compliant,
            'percent_compliant' => $total > 0 ? (int) round(($compliant / $total) * 100) : 0,
        ];
    }

    private function overdueCalibrationCount(): ?int
    {
        if (! class_exists(EquipmentCalibration::class)) {
            return null;
        }

        if (! Schema::hasTable((new EquipmentCalibration)->getTable())) {
            return null;
        }

        return EquipmentCalibration::query()
            ->whereDate('due_at', '<', today())
            ->whereIn('status', [
                EquipmentCalibrationStatus::Scheduled->value,
                EquipmentCalibrationStatus::InProgress->value,
            ])
            ->count();
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<string, int>
     */
    private function statusCounts(string $model, string $column = 'status'): array
    {
        return $model::query()
            ->selectRaw("{$column}, COUNT(*) AS aggregate")
            ->groupBy($column)
            ->pluck('aggregate', $column)
            ->map(static fn (mixed $count): int => (int) $count)
            ->all();
    }
}
