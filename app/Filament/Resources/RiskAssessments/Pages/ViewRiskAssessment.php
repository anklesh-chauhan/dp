<?php

declare(strict_types=1);

namespace App\Filament\Resources\RiskAssessments\Pages;

use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Services\RiskAssessmentTransitionService;
use App\Filament\Resources\RiskAssessments\RiskAssessmentResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewRiskAssessment extends ViewRecord
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => RiskAssessmentResource::canEdit($this->record)),
            $this->transitionAction('submitReview', 'Submit for Review', RiskAssessmentStatus::InReview, [RiskAssessmentStatus::Draft], 'Review:RiskAssessment'),
            $this->transitionAction('returnToDraft', 'Return to Draft', RiskAssessmentStatus::Draft, [RiskAssessmentStatus::InReview], 'Review:RiskAssessment'),
            $this->transitionAction('approve', 'Approve', RiskAssessmentStatus::Approved, [RiskAssessmentStatus::InReview], 'Approve:RiskAssessment', 'success'),
            $this->transitionAction('reject', 'Reject', RiskAssessmentStatus::Rejected, [RiskAssessmentStatus::InReview], 'Approve:RiskAssessment', 'danger'),
            $this->transitionAction(
                'beginMitigation',
                'Begin Mitigation',
                RiskAssessmentStatus::MitigationInProgress,
                [RiskAssessmentStatus::Approved, RiskAssessmentStatus::Monitoring],
                'Mitigate:RiskAssessment',
                requireMitigation: true,
            ),
            $this->transitionAction(
                'beginMonitoring',
                'Begin Monitoring',
                RiskAssessmentStatus::Monitoring,
                [RiskAssessmentStatus::Approved, RiskAssessmentStatus::MitigationInProgress],
                'Monitor:RiskAssessment',
                requireResidual: true,
            ),
            $this->transitionAction('close', 'Close', RiskAssessmentStatus::Closed, [RiskAssessmentStatus::Monitoring], 'Close:RiskAssessment', 'success'),
            $this->transitionAction('cancel', 'Cancel', RiskAssessmentStatus::Cancelled, [
                RiskAssessmentStatus::Draft,
                RiskAssessmentStatus::MitigationInProgress,
            ], 'Manage:RiskAssessment', 'danger'),
        ];
    }

    /** @param list<RiskAssessmentStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        RiskAssessmentStatus $toStatus,
        array $fromStatuses,
        string $permission,
        string $color = 'primary',
        bool $requireMitigation = false,
        bool $requireResidual = false,
    ): Action {
        $schema = [];

        if ($requireMitigation) {
            $schema[] = Textarea::make('mitigation_plan')
                ->required(fn (): bool => blank($this->record->mitigation_plan))
                ->default(fn () => $this->record->mitigation_plan)
                ->rows(4);
        }

        if ($requireResidual) {
            $schema[] = TextInput::make('residual_severity')->numeric()->minValue(1)->maxValue(5)->required()
                ->default(fn () => $this->record->residual_severity);
            $schema[] = TextInput::make('residual_probability')->numeric()->minValue(1)->maxValue(5)->required()
                ->default(fn () => $this->record->residual_probability);
            $schema[] = TextInput::make('residual_detectability')->numeric()->minValue(1)->maxValue(5)->required()
                ->default(fn () => $this->record->residual_detectability);
        }

        $schema[] = ApprovalNarrativeTextarea::decisionRationale(
            name: 'reason',
            label: 'Decision reason',
            helperText: 'Explain what you reviewed and why you are making this decision. This text becomes part of the signed approval record.',
            context: fn (): array => [
                'record_type' => 'Risk Assessment lifecycle decision',
                'subject' => $this->record->risk_number ?? (string) $this->record->getKey(),
                'decision' => $label,
            ],
        );

        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema($schema)
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label, $requireMitigation, $requireResidual): void {
                /** @var User $user */
                $user = auth()->user();

                $mitigationPlan = $requireMitigation
                    ? (filled($data['mitigation_plan'] ?? null) ? $data['mitigation_plan'] : $this->record->mitigation_plan)
                    : null;

                $residualScores = $requireResidual ? [
                    'severity' => (int) $data['residual_severity'],
                    'probability' => (int) $data['residual_probability'],
                    'detectability' => (int) $data['residual_detectability'],
                ] : null;

                app(RiskAssessmentTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    mitigationPlan: $mitigationPlan,
                    residualScores: $residualScores,
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData([
                    'status',
                    'mitigation_plan',
                    'residual_severity',
                    'residual_probability',
                    'residual_detectability',
                    'approved_at',
                    'closed_at',
                ]);

                Notification::make()->success()->title("Risk Assessment: {$label}")->send();
            });
    }
}
