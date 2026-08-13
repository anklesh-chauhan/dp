<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Support;

use App\Models\ApprovalDecision;
use App\Models\ApprovalStepType;
use App\Models\ControlledDocument;
use App\Models\SopApproval;
use App\Models\User;
use App\Support\Formatting\DateFormatSettings;
use DateTimeInterface;

final class PrintApprovalSignatureLayout
{
    public const PREPARED_BY = 'Prepared By';

    public const REVIEWED_BY = 'Reviewed By';

    public const APPROVED_BY = 'Approved By';

    public function __construct(
        private readonly DateFormatSettings $dates,
    ) {}

    /**
     * @return list<array{heading: string, entries: list<array{department: string, signature: string, signature_lines: list<string>, name: string, designation: string}>}>
     */
    public function groups(ControlledDocument $document): array
    {
        /** @var array<string, list<array{department: string, signature: string, signature_lines: list<string>, name: string, designation: string}>> $groups */
        $groups = [
            self::PREPARED_BY => [],
            self::REVIEWED_BY => [],
            self::APPROVED_BY => [],
        ];

        $preparedBy = $document->owner ?? $document->creator;

        if ($preparedBy instanceof User) {
            $groups[self::PREPARED_BY][] = $this->entry(
                department: $preparedBy->department?->name ?? $document->department?->name,
                name: $preparedBy->name,
                designation: $preparedBy->designation?->name,
                signatureLines: [],
            );
        }

        $document->approvals
            ->sortBy(fn (SopApproval $approval): int => (int) ($approval->workflowStep?->step_no ?? PHP_INT_MAX))
            ->each(function (SopApproval $approval) use ($document, &$groups): void {
                $heading = $this->headingFor($approval);
                $groups[$heading] ??= [];
                $groups[$heading][] = $this->entry(
                    department: $approval->workflowStep?->department?->name ?? $document->department?->name,
                    name: $approval->approver?->name,
                    designation: $approval->approver?->designation?->name,
                    signatureLines: $this->signatureLinesFor($approval, $heading),
                );
            });

        return collect($groups)
            ->reject(fn (array $entries): bool => $entries === [])
            ->map(fn (array $entries, string $heading): array => [
                'heading' => $heading,
                'entries' => $entries,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $signatureLines
     * @return array{department: string, signature: string, signature_lines: list<string>, name: string, designation: string}
     */
    private function entry(?string $department, ?string $name, ?string $designation, array $signatureLines): array
    {
        $lines = array_values(array_filter(
            $signatureLines,
            fn (string $line): bool => filled($line),
        ));

        return [
            'department' => filled($department) ? $department : '-',
            'signature' => $lines === [] ? '-' : implode("\n", $lines),
            'signature_lines' => $lines === [] ? ['-'] : $lines,
            'name' => filled($name) ? $name : '-',
            'designation' => filled($designation) ? $designation : '-',
        ];
    }

    /**
     * Build a Part 11 / GxP signature manifestation:
     * electronic signature applied, meaning of the signature, and signed date/time.
     *
     * @return list<string>
     */
    private function signatureLinesFor(SopApproval $approval, string $heading): array
    {
        if (! filled($approval->signature_hash) || ! $approval->approved_at instanceof DateTimeInterface) {
            return [];
        }

        $signedAt = $this->dates->formatDateTime($approval->approved_at);

        if ($signedAt === null) {
            return [];
        }

        $meaning = $this->signatureMeaning($approval, $heading);

        return [
            'Electronically signed',
            $meaning === null ? $signedAt : "{$meaning} · {$signedAt}",
        ];
    }

    private function signatureMeaning(SopApproval $approval, string $heading): ?string
    {
        $decision = $approval->approvalDecision;

        if ($decision instanceof ApprovalDecision
            && filled($decision->name)
            && $decision->code !== ApprovalDecision::PENDING
        ) {
            return $decision->name;
        }

        return match ($heading) {
            self::PREPARED_BY => 'Prepared',
            self::REVIEWED_BY => 'Reviewed',
            self::APPROVED_BY => 'Approved',
            default => filled($heading) ? $heading : null,
        };
    }

    private function headingFor(SopApproval $approval): string
    {
        return match ($approval->workflowStep?->approvalStepType?->code) {
            ApprovalStepType::CHECKER, ApprovalStepType::REVIEW, ApprovalStepType::QA_REVIEW => self::REVIEWED_BY,
            ApprovalStepType::APPROVER, ApprovalStepType::APPROVAL => self::APPROVED_BY,
            default => $approval->workflowStep?->approvalStepType?->name ?? self::REVIEWED_BY,
        };
    }
}
