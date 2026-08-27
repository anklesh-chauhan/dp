<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Contracts\WorkflowDecisionNotifier;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Domain\Shared\Services\WorkflowNotificationDispatcher;
use App\Filament\Resources\ChangeControls\ChangeControlResource;
use App\Filament\Resources\Deviations\DeviationResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
        if (! $approval instanceof QualityApprovalInstance) {
            return;
        }

        $subject = $approval->approvalInstanceSubject();

        if ($subject instanceof Deviation) {
            $this->notifyDeviationDecision($approval, $actor, $decision);

            return;
        }

        if ($subject instanceof ChangeControl) {
            $this->notifyChangeControlDecision($approval, $actor, $decision);
        }
    }

    public function notifyDeviationSubmitted(Deviation $deviation, User $actor): void
    {
        $this->dispatcher->send(
            $this->recipients->currentReviewers($deviation),
            $actor,
            Notification::make()
                ->title('Deviation submitted for your review')
                ->body($this->recordLabel($deviation).' is waiting at the current approval step.')
                ->icon(Heroicon::PaperAirplane)
                ->info()
                ->actions($this->dispatcher->openActions(
                    DeviationResource::getUrl('view', ['record' => $deviation]),
                    'Review',
                )),
        );
    }

    public function notifyChangeControlSubmitted(ChangeControl $changeControl, User $actor): void
    {
        $this->dispatcher->send(
            $this->recipients->currentReviewers($changeControl),
            $actor,
            Notification::make()
                ->title('Change control submitted for your review')
                ->body($this->recordLabel($changeControl).' is waiting at the current approval step.')
                ->icon(Heroicon::PaperAirplane)
                ->info()
                ->actions($this->dispatcher->openActions(
                    ChangeControlResource::getUrl('view', ['record' => $changeControl]),
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

        $this->notifyRecordDecision(
            $subject,
            $actor,
            $decision,
            DeviationResource::getUrl('view', ['record' => $subject]),
            'Deviation',
        );
    }

    public function notifyChangeControlDecision(
        QualityApprovalInstance $approval,
        User $actor,
        ApprovalDecisionCode $decision,
    ): void {
        $subject = $approval->approvalInstanceSubject();

        if (! $subject instanceof ChangeControl) {
            return;
        }

        $this->notifyRecordDecision(
            $subject,
            $actor,
            $decision,
            ChangeControlResource::getUrl('view', ['record' => $subject]),
            'Change control',
        );
    }

    private function notifyRecordDecision(
        Model $record,
        User $actor,
        ApprovalDecisionCode $decision,
        string $url,
        string $noun,
    ): void {
        match ($decision) {
            ApprovalDecisionCode::RETURNED => $this->dispatcher->send(
                $this->stakeholders($record),
                $actor,
                Notification::make()
                    ->title("{$noun} returned for correction")
                    ->body($this->recordLabel($record).' was returned to draft.')
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->warning()
                    ->actions($this->dispatcher->openActions($url)),
            ),
            ApprovalDecisionCode::REJECTED => $this->dispatcher->send(
                $this->stakeholders($record),
                $actor,
                Notification::make()
                    ->title("{$noun} rejected")
                    ->body($this->recordLabel($record).' was rejected during approval.')
                    ->icon(Heroicon::XCircle)
                    ->danger()
                    ->actions($this->dispatcher->openActions($url)),
            ),
            ApprovalDecisionCode::APPROVED => $this->notifyRecordApproved($record, $actor, $url, $noun),
            default => null,
        };
    }

    private function notifyRecordApproved(Model $record, User $actor, string $url, string $noun): void
    {
        $record->refresh();
        $next = $this->recipients->currentQualityApproval($record);

        if ($next instanceof QualityApprovalInstance) {
            $this->dispatcher->send(
                $this->recipients->currentReviewers($record),
                $actor,
                Notification::make()
                    ->title("{$noun} is waiting for your approval")
                    ->body($this->recordLabel($record).' is now at your workflow step.')
                    ->icon(Heroicon::CheckBadge)
                    ->info()
                    ->actions($this->dispatcher->openActions($url, 'Review')),
            );

            return;
        }

        $this->dispatcher->send(
            $this->stakeholders($record),
            $actor,
            Notification::make()
                ->title("{$noun} approved")
                ->body($this->recordLabel($record).' completed approval.')
                ->icon(Heroicon::CheckBadge)
                ->success()
                ->actions($this->dispatcher->openActions($url)),
        );
    }

    /**
     * @return Collection<int, User>
     */
    private function stakeholders(Model $record): Collection
    {
        if ($record instanceof Deviation) {
            return $this->recipients->deviationStakeholders($record);
        }

        if ($record instanceof ChangeControl) {
            return $this->recipients->changeControlStakeholders($record);
        }

        return collect();
    }

    private function recordLabel(Model $record): string
    {
        if ($record instanceof Deviation) {
            return trim(implode(' · ', array_filter([
                $record->deviation_number,
                $record->title,
            ])));
        }

        if ($record instanceof ChangeControl) {
            return trim(implode(' · ', array_filter([
                $record->change_number,
                $record->title,
            ])));
        }

        return (string) $record->getKey();
    }
}
