<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Models\SiteMasterFile;
use App\Enums\ProductModule;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentTrainingAssignment;
use App\Models\DocumentStatus;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

final class InspectorEvidencePackService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly QualityMetricsService $qualityMetricsService,
    ) {}

    /**
     * @return array{
     *     generated_at: string,
     *     generated_by: array{id: int|string|null, name: string|null},
     *     source: array{type: string|null, id: int|string|null, number: string|null, title: string|null},
     *     sop_index: list<array{document_number: string|null, title: string|null, version: string|null, status: string|null}>,
     *     open_quality_event_counts: array<string, int>,
     *     gap_summary: array<string, mixed>|null,
     *     csv_validation_counts: array<string, int>|null,
     *     training_sample: list<array{document_number: string|null, trainee: string|null, assigned_at: string|null, completed_at: string|null}>,
     *     site_master_file: array<string, mixed>|null
     * }
     */
    public function build(
        ScheduleMGapAssessment|SiteMasterFile|null $source,
        User $actor,
    ): array {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('Export:InspectorEvidencePack') && ! $actor->can('View:ScheduleMGapAssessment')) {
            throw new AuthorizationException('You do not have permission to export an inspector evidence pack.');
        }

        $metrics = $this->openQualityEventCounts($actor);

        return [
            'generated_at' => now()->toIso8601String(),
            'generated_by' => [
                'id' => $actor->getKey(),
                'name' => $actor->name,
            ],
            'source' => $this->sourceMeta($source),
            'sop_index' => $this->sopIndexSample(),
            'open_quality_event_counts' => $metrics,
            'gap_summary' => $source instanceof ScheduleMGapAssessment
                ? $this->gapSummary($source)
                : null,
            'csv_validation_counts' => $this->csvValidationCounts(),
            'training_sample' => $this->trainingSample(),
            'site_master_file' => $source instanceof SiteMasterFile
                ? $this->siteMasterFileSummary($source)
                : null,
        ];
    }

    /**
     * @return array{type: string|null, id: int|string|null, number: string|null, title: string|null}
     */
    private function sourceMeta(ScheduleMGapAssessment|SiteMasterFile|null $source): array
    {
        if ($source instanceof ScheduleMGapAssessment) {
            return [
                'type' => 'schedule_m_gap_assessment',
                'id' => $source->getKey(),
                'number' => $source->assessment_number,
                'title' => $source->title,
            ];
        }

        if ($source instanceof SiteMasterFile) {
            return [
                'type' => 'site_master_file',
                'id' => $source->getKey(),
                'number' => $source->smf_number,
                'title' => $source->title,
            ];
        }

        return [
            'type' => null,
            'id' => null,
            'number' => null,
            'title' => null,
        ];
    }

    /**
     * @return list<array{document_number: string|null, title: string|null, version: string|null, status: string|null}>
     */
    private function sopIndexSample(): array
    {
        return ControlledDocument::query()
            ->with('documentStatus')
            ->whereHas('documentStatus', fn ($query) => $query->where('code', DocumentStatus::EFFECTIVE))
            ->orderByDesc('id')
            ->limit(25)
            ->get(['id', 'document_number', 'title', 'version', 'document_status_id'])
            ->map(static fn (ControlledDocument $document): array => [
                'document_number' => $document->document_number,
                'title' => $document->title,
                'version' => $document->version !== null ? (string) $document->version : null,
                'status' => $document->documentStatus?->name,
            ])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function openQualityEventCounts(User $actor): array
    {
        try {
            $snapshot = $this->qualityMetricsService->snapshot($actor);
        } catch (AuthorizationException) {
            return [];
        }

        $open = [];
        foreach ($snapshot['lifecycles'] as $domain => $counts) {
            $open[$domain] = collect($counts)
                ->reject(fn (int $count, string $status): bool => in_array($status, [
                    'closed', 'cancelled', 'rejected', 'completed', 'retired', 'effective',
                    'qualified', 'conditionally_qualified',
                ], true))
                ->sum();
        }

        foreach ($snapshot['overdue'] as $domain => $count) {
            $open["overdue_{$domain}"] = $count;
        }

        return $open;
    }

    /**
     * @return array{
     *     assessment_number: string,
     *     site_name: string,
     *     status: string,
     *     period_label: string|null,
     *     item_counts: array<string, int>,
     *     open_gaps: list<array{clause_ref: string, clause_title: string, status: string, due_at: string|null}>
     * }
     */
    private function gapSummary(ScheduleMGapAssessment $assessment): array
    {
        $assessment->loadMissing('items');

        $itemCounts = $assessment->items
            ->groupBy(fn (Model $item): string => $item->status instanceof ScheduleMGapItemStatus
                ? $item->status->value
                : (string) $item->status)
            ->map(fn ($group): int => $group->count())
            ->all();

        $openGaps = $assessment->items
            ->filter(fn (Model $item): bool => in_array(
                $item->status instanceof ScheduleMGapItemStatus ? $item->status : ScheduleMGapItemStatus::tryFrom((string) $item->status),
                [ScheduleMGapItemStatus::Gap, ScheduleMGapItemStatus::Partial, ScheduleMGapItemStatus::NotAssessed],
                true,
            ))
            ->values()
            ->map(static fn (Model $item): array => [
                'clause_ref' => (string) $item->clause_ref,
                'clause_title' => (string) $item->clause_title,
                'status' => $item->status instanceof ScheduleMGapItemStatus
                    ? $item->status->value
                    : (string) $item->status,
                'due_at' => $item->due_at?->toIso8601String(),
            ])
            ->all();

        return [
            'assessment_number' => $assessment->assessment_number,
            'site_name' => $assessment->site_name,
            'status' => $assessment->status->value,
            'period_label' => $assessment->period_label,
            'item_counts' => $itemCounts,
            'open_gaps' => $openGaps,
        ];
    }

    /**
     * @return array<string, int>|null
     */
    private function csvValidationCounts(): ?array
    {
        if (! Schema::hasTable('csv_validation_projects')) {
            return null;
        }

        return CsvValidationProject::query()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(static fn (mixed $count): int => (int) $count)
            ->all();
    }

    /**
     * @return list<array{document_number: string|null, trainee: string|null, assigned_at: string|null, completed_at: string|null}>
     */
    private function trainingSample(): array
    {
        if (! Schema::hasTable('controlled_document_training_assignments')) {
            return [];
        }

        return ControlledDocumentTrainingAssignment::query()
            ->with(['document:id,document_number', 'user:id,name'])
            ->orderByDesc('id')
            ->limit(25)
            ->get()
            ->map(static fn (ControlledDocumentTrainingAssignment $assignment): array => [
                'document_number' => $assignment->document?->document_number,
                'trainee' => $assignment->user?->name,
                'assigned_at' => $assignment->assigned_at?->toIso8601String(),
                'completed_at' => $assignment->completed_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array{smf_number: string, title: string, status: string, version: int, published_at: string|null, section_keys: list<string>}
     */
    private function siteMasterFileSummary(SiteMasterFile $file): array
    {
        return [
            'smf_number' => $file->smf_number,
            'title' => $file->title,
            'status' => $file->status->value,
            'version' => (int) $file->version,
            'published_at' => $file->published_at?->toIso8601String(),
            'section_keys' => array_keys($file->sections ?? []),
        ];
    }
}
