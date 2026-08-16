<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Contracts\WorkflowDecisionNotifier;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Domain\Shared\Services\WorkflowNotificationDispatcher;
use App\Filament\Resources\Deviations\DeviationResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class QualityWorkflowNotificationService implements WorkflowDecisionNotifier
{
    public function __construct(
        private readonly QualityWorkflowRecipientFinder $recipients,
        private readonly WorkflowNotificationDispatcher $dispatcher,
    ) {}

    public function notifyDecision(
        ApprovalInstance $approval,
        User $actor,
        ApprovalDecisionCode $decision,
    ): void {
        if ($approval instanceof QualityApprovalInstance) {
            $this->notifyDeviationDecision($approval, $actor, $decision);
        }
    }

    public function notifyDeviationSubmitted(Deviation $deviation, User $actor): void
    {
        $this->dispatcher->send(
            $this->recipients->currentDeviationReviewers($deviation),
            $actor,
            Notification::make()
                ->title('Deviation submitted for your review')
                ->body($this->deviationLabel($deviation).' is waiting at the current approval step.')
                ->icon(Heroicon::PaperAirplane)
                ->info()
                ->actions($this->dispatcher->openActions(
                    DeviationResource::getUrl('view', ['record' => $deviation]),
                    'Review',
                )),
        );
    }

    public function notifyDeviationDecision(
        QualityApprovalInstance $approval,
        User $actor,
        ApprovalDecisionCode $decision,
    ): void {
        $subject = $approval->approvalInstanceSubject();

        if (! $subject instanceof Deviation) {
            return;
        }

        match ($decision) {
            ApprovalDecisionCode::RETURNED => $this->dispatcher->send(
                $this->recipients->deviationStakeholders($subject),
                $actor,
                Notification::make()
                    ->title('Deviation returned for correction')
                    ->body($this->deviationLabel($subject).' was returned to draft.')
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->warning()
                    ->actions($this->dispatcher->openActions(DeviationResource::getUrl('view', ['record' => $subject]))),
            ),
            ApprovalDecisionCode::REJECTED => $this->dispatcher->send(
                $this->recipients->deviationStakeholders($subject),
                $actor,
                Notification::make()
                    ->title('Deviation rejected')
                    ->body($this->deviationLabel($subject).' was rejected during approval.')
                    ->icon(Heroicon::XCircle)
                    ->danger()
                    ->actions($this->dispatcher->openActions(DeviationResource::getUrl('view', ['record' => $subject]))),
            ),
            ApprovalDecisionCode::APPROVED => $this->notifyDeviationApproved($subject, $actor),
            default => null,
        };
    }

    private function notifyDeviationApproved(Deviation $deviation, User $actor): void
    {
        $deviation->refresh();
        $next = $this->recipients->currentQualityApproval($deviation);

        if ($next instanceof QualityApprovalInstance) {
            $this->dispatcher->send(
                $this->recipients->currentDeviationReviewers($deviation),
                $actor,
                Notification::make()
                    ->title('Deviation is waiting for your approval')
                    ->body($this->deviationLabel($deviation).' is now at your workflow step.')
                    ->icon(Heroicon::CheckBadge)
                    ->info()
                    ->actions($this->dispatcher->openActions(
                        DeviationResource::getUrl('view', ['record' => $deviation]),
                        'Review',
                    )),
            );

            return;
        }

        $this->dispatcher->send(
            $this->recipients->deviationStakeholders($deviation),
            $actor,
            Notification::make()
                ->title('Deviation approved')
                ->body($this->deviationLabel($deviation).' completed approval.')
                ->icon(Heroicon::CheckBadge)
                ->success()
                ->actions($this->dispatcher->openActions(DeviationResource::getUrl('view', ['record' => $deviation]))),
        );
    }

    private function deviationLabel(Deviation $deviation): string
    {
        return trim(implode(' · ', array_filter([
            $deviation->deviation_number,
            $deviation->title,
        ])));
    }
}
