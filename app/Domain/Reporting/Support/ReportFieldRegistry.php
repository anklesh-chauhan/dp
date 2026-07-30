<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Support;

use App\Domain\QMS\Models\ChangeControl;
use App\Domain\Reporting\Enums\ReportScope;
use App\Models\ControlledDocument;
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
                'department' => ['label' => 'Department', 'group' => 'Metadata'],
                'owner' => ['label' => 'Document Owner', 'group' => 'Metadata'],
                'effective_date' => ['label' => 'Effective Date', 'group' => 'Metadata'],
                'review_date' => ['label' => 'Review Date', 'group' => 'Metadata'],
                'variables' => ['label' => 'Template Variables', 'group' => 'Content'],
                'sections' => ['label' => 'Controlled Sections', 'group' => 'Content'],
                'approvals' => ['label' => 'Approval Signatures', 'group' => 'Compliance'],
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
        };
    }

    /** @return list<array{key: string, label: string, group: string, enabled: bool, width: string, hide_when_empty: bool, page_break_before: bool}> */
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
                'page_break_before' => false,
            ])
            ->values()
            ->all();
    }

    /** @param array<int, mixed> $fields
     * @return list<array{key: string, label: string, group: string, enabled: bool, width: string, hide_when_empty: bool, page_break_before: bool}>
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
                'page_break_before' => false,
            ]);

        return $normalized->concat($missing)->values()->all();
    }

    public function value(Model $record, string $key): string|int
    {
        return match (true) {
            $record instanceof ControlledDocument => $this->controlledDocumentValue($record, $key),
            $record instanceof ChangeControl => $this->changeControlValue($record, $key),
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
            'effective_date' => $document->effective_date?->toDateString() ?? '',
            'review_date' => $document->review_date?->toDateString() ?? '',
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
}
