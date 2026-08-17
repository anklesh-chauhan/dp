<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\AuditFindingSeverity;
use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\InternalAudit;
use App\Domain\QMS\Models\ManagementReview;
use App\Domain\QMS\Models\ProductQualityReview;
use App\Domain\QMS\Models\ProductRecall;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\SupplierQualification;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;

final class ManagementReviewInputAssembler
{
    private const array TERMINAL_DEVIATION_STATUSES = [
        DeviationStatus::Closed,
        DeviationStatus::Rejected,
        DeviationStatus::Cancelled,
    ];

    private const array TERMINAL_CAPA_STATUSES = [
        CapaStatus::Effective,
        CapaStatus::Closed,
        CapaStatus::Cancelled,
    ];

    private const array TERMINAL_AUDIT_STATUSES = [
        InternalAuditStatus::Closed,
        InternalAuditStatus::Cancelled,
    ];

    private const array TERMINAL_FINDING_DISPOSITIONS = [
        AuditFindingDisposition::Closed,
        AuditFindingDisposition::Rejected,
        AuditFindingDisposition::Cancelled,
    ];

    private const array TERMINAL_RECALL_STATUSES = [
        ProductRecallStatus::Closed,
        ProductRecallStatus::Cancelled,
    ];

    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly QualityMetricsService $qualityMetricsService,
    ) {}

    /**
     * @return list<array{title: string, summary: string, stats: array<string, int>}>
     */
    public function assemble(ManagementReview $review, User $actor): array
    {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        $snapshot = $this->qualityMetricsService->snapshot($actor);

        $openDeviations = $this->openDeviationCount();
        $openCapas = $this->openCapaCount();
        $overdueDeviations = (int) ($snapshot['overdue']['deviations'] ?? 0);
        $overdueCapas = (int) ($snapshot['overdue']['capas'] ?? 0);

        $openInternalAudits = $this->openInternalAuditCount();
        $criticalFindings = $this->openCriticalFindingCount();

        $sections = [
            [
                'title' => 'Management review context',
                'summary' => sprintf(
                    'Suggested inputs assembled for %s covering the configured review period. Counts are live aggregates without record titles or personal data.',
                    $review->review_number ?? 'this management review',
                ),
                'stats' => [
                    'required_input_types' => count($review->required_inputs ?? []),
                ],
            ],
            [
                'title' => 'Deviations and CAPAs',
                'summary' => sprintf(
                    'Open deviations: %d (%d overdue). Open CAPAs: %d (%d overdue).',
                    $openDeviations,
                    $overdueDeviations,
                    $openCapas,
                    $overdueCapas,
                ),
                'stats' => [
                    'open_deviations' => $openDeviations,
                    'overdue_deviations' => $overdueDeviations,
                    'open_capas' => $openCapas,
                    'overdue_capas' => $overdueCapas,
                ],
            ],
            [
                'title' => 'Internal audits and findings',
                'summary' => sprintf(
                    'Open internal audits: %d. Open critical findings: %d.',
                    $openInternalAudits,
                    $criticalFindings,
                ),
                'stats' => [
                    'open_internal_audits' => $openInternalAudits,
                    'open_critical_findings' => $criticalFindings,
                    'overdue_internal_audits' => (int) ($snapshot['overdue']['internal_audits'] ?? 0),
                    'overdue_audit_findings' => (int) ($snapshot['overdue']['audit_findings'] ?? 0),
                ],
            ],
        ];

        if (class_exists(ProductQualityReview::class)) {
            $pqrCounts = $snapshot['lifecycles']['product_quality_reviews'] ?? [];
            $sections[] = [
                'title' => 'Product quality reviews',
                'summary' => sprintf(
                    'PQR records by status (%d total). Overdue in-progress reviews: %d.',
                    array_sum($pqrCounts),
                    (int) ($snapshot['overdue']['product_quality_reviews'] ?? 0),
                ),
                'stats' => $this->intStats($pqrCounts),
            ];
        }

        $openRecalls = $this->openProductRecallCount();
        $sections[] = [
            'title' => 'Product recalls',
            'summary' => sprintf('Open product recalls: %d.', $openRecalls),
            'stats' => [
                'open_product_recalls' => $openRecalls,
                'overdue_product_recalls' => (int) ($snapshot['overdue']['product_recalls'] ?? 0),
            ],
        ];

        $expiringSuppliers = $this->supplierQualificationsExpiringWithinDays(90);
        $sections[] = [
            'title' => 'Supplier qualifications',
            'summary' => sprintf(
                'Qualified suppliers with qualification_expires_at within 90 days: %d.',
                $expiringSuppliers,
            ),
            'stats' => [
                'expiring_within_90_days' => $expiringSuppliers,
                'overdue_reviews' => (int) ($snapshot['overdue']['supplier_qualifications'] ?? 0),
            ],
        ];

        $overdueMonitoringRisks = $this->overdueMonitoringRiskAssessments();
        $sections[] = [
            'title' => 'Risk assessments',
            'summary' => sprintf(
                'Monitoring risk assessments past review_due_at: %d. Combined overdue (mitigation or monitoring): %d.',
                $overdueMonitoringRisks,
                (int) ($snapshot['overdue']['risk_assessments'] ?? 0),
            ),
            'stats' => [
                'monitoring_past_review_due' => $overdueMonitoringRisks,
                'overdue_risk_assessments' => (int) ($snapshot['overdue']['risk_assessments'] ?? 0),
            ],
        ];

        return $sections;
    }

    /**
     * @param  list<array{title: string, summary: string, stats: array<string, int>}>  $sections
     */
    public function toMarkdown(array $sections): string
    {
        $lines = ['# Suggested management review inputs', ''];

        foreach ($sections as $section) {
            $lines[] = '## '.$section['title'];
            $lines[] = $section['summary'];
            $lines[] = '';
            foreach ($section['stats'] as $key => $value) {
                $lines[] = sprintf('- %s: %d', str_replace('_', ' ', $key), $value);
            }
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines))."\n";
    }

    private function openDeviationCount(): int
    {
        return Deviation::query()
            ->whereNotIn('status', array_map(
                static fn (DeviationStatus $status): string => $status->value,
                self::TERMINAL_DEVIATION_STATUSES,
            ))
            ->count();
    }

    private function openCapaCount(): int
    {
        return Capa::query()
            ->whereNotIn('status', array_map(
                static fn (CapaStatus $status): string => $status->value,
                self::TERMINAL_CAPA_STATUSES,
            ))
            ->count();
    }

    private function openInternalAuditCount(): int
    {
        return InternalAudit::query()
            ->whereNotIn('status', array_map(
                static fn (InternalAuditStatus $status): string => $status->value,
                self::TERMINAL_AUDIT_STATUSES,
            ))
            ->count();
    }

    private function openCriticalFindingCount(): int
    {
        return AuditFinding::query()
            ->where('severity', AuditFindingSeverity::Critical->value)
            ->whereNotIn('disposition', array_map(
                static fn (AuditFindingDisposition $disposition): string => $disposition->value,
                self::TERMINAL_FINDING_DISPOSITIONS,
            ))
            ->count();
    }

    private function openProductRecallCount(): int
    {
        return ProductRecall::query()
            ->whereNotIn('status', array_map(
                static fn (ProductRecallStatus $status): string => $status->value,
                self::TERMINAL_RECALL_STATUSES,
            ))
            ->count();
    }

    private function supplierQualificationsExpiringWithinDays(int $days): int
    {
        return SupplierQualification::query()
            ->whereIn('status', [
                SupplierQualificationStatus::Qualified->value,
                SupplierQualificationStatus::ConditionallyQualified->value,
            ])
            ->whereNotNull('qualification_expires_at')
            ->whereDate('qualification_expires_at', '>=', today())
            ->whereDate('qualification_expires_at', '<=', today()->addDays($days))
            ->count();
    }

    private function overdueMonitoringRiskAssessments(): int
    {
        return RiskAssessment::query()
            ->where('status', RiskAssessmentStatus::Monitoring->value)
            ->whereDate('review_due_at', '<', today())
            ->count();
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    private function intStats(array $counts): array
    {
        $stats = [];
        foreach ($counts as $key => $value) {
            $stats[(string) $key] = (int) $value;
        }

        return $stats;
    }
}
