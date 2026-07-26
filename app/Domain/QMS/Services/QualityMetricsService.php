<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

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
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

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
                    ->whereDate('mitigation_due_at', '<', today())
                    ->whereIn('status', [
                        RiskAssessmentStatus::Approved->value,
                        RiskAssessmentStatus::MitigationInProgress->value,
                    ])->count(),
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
            ],
        ];
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
