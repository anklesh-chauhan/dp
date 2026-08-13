<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Support;

use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\CsvRequirement;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\Reporting\Enums\ReportScope;
use App\Models\ControlledDocument;
use App\Support\Formatting\DateFormatSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ReportFieldRegistry
{
    /** @return array<string, array{label: string, group: string}> */
    public function definitions(ReportScope $scope): array
    {
        return match ($scope) {
            ReportScope::ControlledDocument => [
                'organization' => ['label' => 'Organization Header', 'group' => 'Header'],
                'document_identity' => ['label' => 'Document Number, Title & Version', 'group' => 'Header'],
                'status' => ['label' => 'Document Status', 'group' => 'Header'],
                'issuance_number' => ['label' => 'Issuance Number', 'group' => 'Metadata'],
                'department' => ['label' => 'Department', 'group' => 'Metadata'],
                'owner' => ['label' => 'Document Owner', 'group' => 'Metadata'],
                'effective_date' => ['label' => 'Effective Date', 'group' => 'Metadata'],
                'review_date' => ['label' => 'Review Date', 'group' => 'Metadata'],
                'variables' => ['label' => 'Template Variables', 'group' => 'Content'],
                'approvals' => ['label' => 'Approval Signatures', 'group' => 'Compliance'],
                'sections' => ['label' => 'Controlled Sections', 'group' => 'Content'],
                'audit_reference' => ['label' => 'Printed By / Printed At', 'group' => 'Compliance'],
                'footer' => ['label' => 'Organization Footer', 'group' => 'Footer'],
            ],
            ReportScope::ChangeControl => [
                'change_number' => ['label' => 'Change Number', 'group' => 'Identification'],
                'title' => ['label' => 'Title', 'group' => 'Identification'],
                'status' => ['label' => 'Status', 'group' => 'Identification'],
                'department' => ['label' => 'Department', 'group' => 'Ownership'],
                'requester' => ['label' => 'Requested By', 'group' => 'Ownership'],
                'owner' => ['label' => 'Owner', 'group' => 'Ownership'],
                'description' => ['label' => 'Description', 'group' => 'Investigation'],
                'rationale' => ['label' => 'Rationale', 'group' => 'Investigation'],
                'document_impacts' => ['label' => 'Controlled Document Impacts', 'group' => 'Impact'],
                'milestones' => ['label' => 'Implementation & Effectiveness Milestones', 'group' => 'Lifecycle'],
                'audit_events' => ['label' => 'Decision & Audit Trail', 'group' => 'Compliance'],
            ],
            ReportScope::DocumentDistribution => [
                'document_number' => ['label' => 'Document Number', 'group' => 'Document'],
                'title' => ['label' => 'Title', 'group' => 'Document'],
                'version' => ['label' => 'Version', 'group' => 'Document'],
                'status' => ['label' => 'Status', 'group' => 'Document'],
                'department' => ['label' => 'Department', 'group' => 'Ownership'],
                'owner' => ['label' => 'Owner', 'group' => 'Ownership'],
                'effective_date' => ['label' => 'Effective Date', 'group' => 'Lifecycle'],
                'review_date' => ['label' => 'Review Date', 'group' => 'Lifecycle'],
                'active_copy_count' => ['label' => 'Active Controlled Copies', 'group' => 'Distribution'],
            ],
            ReportScope::CsvValidationTraceability => [
                'requirement_identifier' => ['label' => 'Requirement ID', 'group' => 'Requirement'],
                'requirement_statement' => ['label' => 'Requirement', 'group' => 'Requirement'],
                'requirement_status' => ['label' => 'Requirement Status', 'group' => 'Requirement'],
                'criticality' => ['label' => 'Criticality', 'group' => 'Risk'],
                'gxp_relevant' => ['label' => 'GxP Relevant', 'group' => 'Data Integrity'],
                'data_integrity_relevant' => ['label' => 'Data Integrity Relevant', 'group' => 'Data Integrity'],
                'risk_identifiers' => ['label' => 'Linked Risks', 'group' => 'Risk'],
                'test_identifiers' => ['label' => 'Linked Test Cases', 'group' => 'Verification'],
                'execution_results' => ['label' => 'Execution Results', 'group' => 'Verification'],
                'evidence_references' => ['label' => 'Evidence References', 'group' => 'ALCOA+ Traceability'],
                'deviation_references' => ['label' => 'Deviation References', 'group' => 'ALCOA+ Traceability'],
                'independent_review' => ['label' => 'Independent Review', 'group' => 'ALCOA+ Traceability'],
            ],
            ReportScope::CsvValidationSummary => [
                'project_number' => ['label' => 'Project Number', 'group' => 'System'],
                'system_identity' => ['label' => 'System / Version', 'group' => 'System'],
                'status' => ['label' => 'Validation Status', 'group' => 'System'],
                'intended_use' => ['label' => 'Intended Use', 'group' => 'Validation'],
                'gxp_classification' => ['label' => 'GxP Classification', 'group' => 'GMP'],
                'regulatory_scope' => ['label' => 'Regulatory Scope', 'group' => 'GMP'],
                'validation_strategy' => ['label' => 'Validation Strategy', 'group' => 'Validation'],
                'validation_summary' => ['label' => 'Validation Summary', 'group' => 'Validation'],
                'traceability_totals' => ['label' => 'Traceability Totals', 'group' => 'ALCOA+ Traceability'],
                'test_outcomes' => ['label' => 'Test Outcomes', 'group' => 'Verification'],
                'release_baseline' => ['label' => 'Release Baseline', 'group' => 'Configuration'],
                'ownership' => ['label' => 'Business / System / Quality Owners', 'group' => 'Governance'],
                'release_decision' => ['label' => 'Release Decision', 'group' => 'Governance'],
                'audit_events' => ['label' => 'Signed Lifecycle Audit Trail', 'group' => 'ALCOA+ Traceability'],
            ],
        };
    }

    /** @return list<array{key: string, label: string, group: string, enabled: bool, width: string, hide_when_empty: bool, show_label: bool, show_section_titles: bool, page_break_before: bool}> */
    public function defaultFields(ReportScope $scope): array
    {
        return collect($this->definitions($scope))
            ->map(fn (array $definition, string $key): array => [
                'key' => $key,
                'label' => $definition['label'],
                'group' => $definition['group'],
                'enabled' => true,
                'width' => 'full',
                'hide_when_empty' => false,
                'show_label' => true,
                'show_section_titles' => true,
                'page_break_before' => false,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{key: string, label: string, group: string, enabled: bool, width: string, hide_when_empty: bool, show_label: bool, show_section_titles: bool, page_break_before: bool}> */
    public function defaultGmpControlledDocumentFields(): array
    {
        $disabledKeys = [
            'organization',
            'document_identity',
            'status',
            'issuance_number',
            'department',
            'owner',
            'effective_date',
            'review_date',
            'variables',
            'audit_reference',
            'footer',
        ];

        return collect($this->defaultFields(ReportScope::ControlledDocument))
            ->map(function (array $field) use ($disabledKeys): array {
                if (in_array($field['key'], $disabledKeys, true)) {
                    $field['enabled'] = false;

                    return $field;
                }

                if ($field['key'] === 'sections') {
                    $field['enabled'] = true;
                    $field['show_label'] = false;
                    $field['page_break_before'] = true;

                    return $field;
                }

                if ($field['key'] === 'approvals') {
                    $field['enabled'] = true;
                    $field['show_label'] = true;

                    return $field;
                }

                return $field;
            })
            ->all();
    }

    /** @param array<int, mixed> $fields
     * @return list<array{key: string, label: string, group: string, enabled: bool, width: string, hide_when_empty: bool, show_label: bool, show_section_titles: bool, page_break_before: bool}>
     */
    public function normalize(ReportScope $scope, array $fields): array
    {
        $definitions = $this->definitions($scope);
        $submittedKeys = collect($fields)->pluck('key');

        if ($submittedKeys->duplicates()->isNotEmpty() || $submittedKeys->diff(array_keys($definitions))->isNotEmpty()) {
            throw ValidationException::withMessages([
                'fields' => 'The template contains duplicate or unsupported system fields.',
            ]);
        }

        $normalized = collect($fields)
            ->filter(fn (mixed $field): bool => is_array($field) && isset($definitions[$field['key'] ?? '']))
            ->map(function (array $field) use ($definitions): array {
                $key = (string) $field['key'];

                return [
                    'key' => $key,
                    'label' => Str::limit(Str::squish((string) ($field['label'] ?? $definitions[$key]['label'])), 80, '') ?: $definitions[$key]['label'],
                    'group' => $definitions[$key]['group'],
                    'enabled' => (bool) Arr::get($field, 'enabled', false),
                    'width' => in_array(Arr::get($field, 'width'), ['full', 'half'], true)
                        ? Arr::get($field, 'width')
                        : 'full',
                    'hide_when_empty' => (bool) Arr::get($field, 'hide_when_empty', false),
                    'show_label' => (bool) Arr::get($field, 'show_label', true),
                    'show_section_titles' => (bool) Arr::get($field, 'show_section_titles', true),
                    'page_break_before' => (bool) Arr::get($field, 'page_break_before', false),
                ];
            });

        $missing = collect($definitions)
            ->except($normalized->pluck('key')->all())
            ->map(fn (array $definition, string $key): array => [
                'key' => $key,
                'label' => $definition['label'],
                'group' => $definition['group'],
                'enabled' => false,
                'width' => 'full',
                'hide_when_empty' => false,
                'show_label' => true,
                'show_section_titles' => true,
                'page_break_before' => false,
            ]);

        return $normalized->concat($missing)->values()->all();
    }

    public function value(Model $record, string $key): string|int
    {
        return match (true) {
            $record instanceof ControlledDocument => $this->controlledDocumentValue($record, $key),
            $record instanceof ChangeControl => $this->changeControlValue($record, $key),
            $record instanceof CsvRequirement => $this->csvRequirementValue($record, $key),
            $record instanceof CsvValidationProject => $this->csvValidationProjectValue($record, $key),
            default => '',
        };
    }

    private function controlledDocumentValue(ControlledDocument $document, string $key): string|int
    {
        return match ($key) {
            'document_number' => (string) $document->document_number,
            'title' => (string) $document->title,
            'version' => (int) $document->version,
            'status' => (string) $document->documentStatus?->name,
            'department' => (string) $document->department?->name,
            'owner' => (string) $document->owner?->name,
            'effective_date' => app(DateFormatSettings::class)->formatDate($document->effective_date) ?? '',
            'review_date' => app(DateFormatSettings::class)->formatDate($document->review_date) ?? '',
            'active_copy_count' => $document->active_issuances_count ?? $document->activeIssuances()->count(),
            default => '',
        };
    }

    private function changeControlValue(ChangeControl $changeControl, string $key): string
    {
        return match ($key) {
            'change_number' => (string) $changeControl->change_number,
            'title' => (string) $changeControl->title,
            'status' => str($changeControl->status->value)->replace('_', ' ')->title()->toString(),
            'department' => (string) $changeControl->department?->name,
            'requester' => (string) $changeControl->requester?->name,
            'owner' => (string) $changeControl->owner?->name,
            'description' => (string) $changeControl->description,
            'rationale' => (string) $changeControl->rationale,
            default => '',
        };
    }

    private function csvRequirementValue(CsvRequirement $requirement, string $key): string
    {
        $testCases = $requirement->testCases;
        $executions = $testCases->flatMap->executions;

        return match ($key) {
            'requirement_identifier' => (string) $requirement->requirement_identifier,
            'requirement_statement' => (string) $requirement->statement,
            'requirement_status' => str((string) $requirement->status?->value)->replace('_', ' ')->title()->toString(),
            'criticality' => str((string) $requirement->criticality?->value)->title()->toString(),
            'gxp_relevant' => $requirement->gxp_relevant ? 'Yes' : 'No',
            'data_integrity_relevant' => $requirement->data_integrity_relevant ? 'Yes' : 'No',
            'risk_identifiers' => $requirement->risks->pluck('risk_identifier')->filter()->implode('; '),
            'test_identifiers' => $testCases->pluck('test_identifier')->filter()->implode('; '),
            'execution_results' => $executions->map(fn ($execution): string => sprintf(
                '%s: %s',
                $execution->testCase?->test_identifier ?? $execution->execution_no,
                str((string) $execution->result?->value)->title(),
            ))->implode('; '),
            'evidence_references' => $executions->pluck('evidence_summary')->filter()->implode('; '),
            'deviation_references' => $executions->pluck('deviation.deviation_number')->filter()->implode('; '),
            'independent_review' => $executions->isNotEmpty() && $executions->every(
                fn ($execution): bool => $execution->reviewed_at !== null && $execution->reviewed_by !== $execution->executed_by,
            ) ? 'Complete' : 'Pending',
            default => '',
        };
    }

    private function csvValidationProjectValue(CsvValidationProject $project, string $key): string|int
    {
        return match ($key) {
            'project_number' => (string) $project->project_number,
            'system_identity' => trim("{$project->system_name} {$project->system_version}"),
            'status' => str($project->status->value)->replace('_', ' ')->title()->toString(),
            'intended_use' => (string) $project->intended_use,
            'gxp_classification' => $project->is_gxp ? 'GxP - '.str((string) $project->gxp_criticality?->value)->title() : 'Non-GxP',
            'regulatory_scope' => collect($project->regulatory_scope)->implode(', '),
            'validation_strategy' => (string) $project->validation_strategy,
            'validation_summary' => (string) $project->validation_summary,
            'traceability_totals' => "{$project->requirements_count} requirements; {$project->test_cases_count} tests",
            'test_outcomes' => $project->testExecutions->groupBy(
                fn ($execution): string => $execution->result?->value ?? 'not_run',
            )->map(fn ($executions, string $result): string => str($result)->title().': '.$executions->count())->implode('; '),
            'release_baseline' => collect($project->release_baseline)->map(
                fn (mixed $value, string $name): string => "{$name}: {$value}",
            )->implode('; '),
            'ownership' => collect([$project->businessOwner?->name, $project->systemOwner?->name, $project->qualityOwner?->name])->filter()->implode(' / '),
            'release_decision' => $project->released_at === null
                ? 'Not released'
                : 'Released '.(app(DateFormatSettings::class)->formatDate($project->released_at) ?? '').' by '.($project->releaser?->name ?? 'Unknown'),
            default => '',
        };
    }
}
