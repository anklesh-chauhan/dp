<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupplierQualifications\Pages;

use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Services\SupplierQualificationTransitionService;
use App\Filament\Resources\SupplierQualifications\SupplierQualificationResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewSupplierQualification extends ViewRecord
{
    protected static string $resource = SupplierQualificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => SupplierQualificationResource::canEdit($this->record)),
            $this->transitionAction('beginAssessment', 'Begin Assessment', SupplierQualificationStatus::UnderAssessment, [
                SupplierQualificationStatus::Draft,
                SupplierQualificationStatus::Qualified,
                SupplierQualificationStatus::ConditionallyQualified,
                SupplierQualificationStatus::Suspended,
                SupplierQualificationStatus::Expired,
            ], 'Assess:SupplierQualification', requireRationaleFromSuspended: true),
            $this->transitionAction('requireAudit', 'Require Audit', SupplierQualificationStatus::AuditRequired, [SupplierQualificationStatus::UnderAssessment], 'Audit:SupplierQualification'),
            $this->transitionAction(
                'qualify',
                'Qualify',
                SupplierQualificationStatus::Qualified,
                [SupplierQualificationStatus::UnderAssessment, SupplierQualificationStatus::AuditRequired],
                'Approve:SupplierQualification',
                'success',
                requireRationale: true,
            ),
            $this->transitionAction(
                'conditionallyQualify',
                'Conditionally Qualify',
                SupplierQualificationStatus::ConditionallyQualified,
                [SupplierQualificationStatus::UnderAssessment, SupplierQualificationStatus::AuditRequired],
                'Approve:SupplierQualification',
                'warning',
                requireRationale: true,
            ),
            $this->transitionAction('suspend', 'Suspend', SupplierQualificationStatus::Suspended, [
                SupplierQualificationStatus::Qualified,
                SupplierQualificationStatus::ConditionallyQualified,
            ], 'Suspend:SupplierQualification', 'danger'),
            $this->transitionAction('disqualify', 'Disqualify', SupplierQualificationStatus::Disqualified, [
                SupplierQualificationStatus::UnderAssessment,
                SupplierQualificationStatus::AuditRequired,
                SupplierQualificationStatus::Qualified,
                SupplierQualificationStatus::ConditionallyQualified,
                SupplierQualificationStatus::Suspended,
                SupplierQualificationStatus::Expired,
            ], 'Disqualify:SupplierQualification', 'danger'),
            $this->transitionAction('expire', 'Mark Expired', SupplierQualificationStatus::Expired, [
                SupplierQualificationStatus::Qualified,
                SupplierQualificationStatus::ConditionallyQualified,
            ], 'Review:SupplierQualification', 'warning'),
            $this->transitionAction('cancel', 'Cancel', SupplierQualificationStatus::Cancelled, [
                SupplierQualificationStatus::Draft,
                SupplierQualificationStatus::UnderAssessment,
            ], 'Manage:SupplierQualification', 'danger'),
        ];
    }

    /** @param list<SupplierQualificationStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        SupplierQualificationStatus $toStatus,
        array $fromStatuses,
        string $permission,
        string $color = 'primary',
        bool $requireRationale = false,
        bool $requireRationaleFromSuspended = false,
    ): Action {
        $schema = [];

        if ($requireRationale || $requireRationaleFromSuspended) {
            $schema[] = Textarea::make('qualification_rationale')
                ->label('Qualification rationale')
                ->required(fn (): bool => $requireRationale
                    || ($requireRationaleFromSuspended && $this->record->status === SupplierQualificationStatus::Suspended))
                ->default(fn () => $this->record->qualification_rationale)
                ->rows(4);
        }

        $schema[] = ApprovalNarrativeTextarea::decisionRationale(
            name: 'reason',
            label: 'Decision reason',
            helperText: 'Explain what you reviewed and why you are making this decision. This text becomes part of the signed approval record.',
            context: fn (): array => [
                'record_type' => 'Supplier Qualification lifecycle decision',
                'subject' => $this->record->supplier_number ?? (string) $this->record->getKey(),
                'decision' => $label,
            ],
        );

        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema($schema)
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(SupplierQualificationTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    qualificationRationale: $data['qualification_rationale'] ?? null,
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData([
                    'status',
                    'qualification_rationale',
                    'qualified_at',
                    'suspended_at',
                    'disqualified_at',
                ]);

                Notification::make()->success()->title("Supplier Qualification: {$label}")->send();
            });
    }
}
