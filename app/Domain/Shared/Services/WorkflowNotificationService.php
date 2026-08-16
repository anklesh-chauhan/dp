<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Contracts\WorkflowDecisionNotifier;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Filament\Resources\DocumentTemplateApprovalInstances\DocumentTemplateApprovalInstanceResource;
use App\Filament\Resources\DocumentTemplates\DocumentTemplateResource;
use App\Filament\Resources\LogDocuments\LogDocumentResource;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use App\Models\ControlledDocumentSectionReviewComment;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplateApprovalInstance;
use App\Models\DocumentTemplateVersion;
use App\Models\SopApproval;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class WorkflowNotificationService implements WorkflowDecisionNotifier
{
    public function __construct(
        private readonly WorkflowRecipientFinder $recipients,
        private readonly WorkflowNotificationDispatcher $dispatcher,
    ) {}

    public function notifySubjectSubmitted(ApprovableSubject $subject, User $actor): void
    {
        if ($subject instanceof ControlledDocument) {
            $this->notifyDocumentSubmitted($subject, $actor);
        }
    }

    public function notifyDecision(
        ApprovalInstance $approval,
        User $actor,
        ApprovalDecisionCode $decision,
    ): void {
        if ($approval instanceof SopApproval) {
            $this->notifyDocumentDecision($approval, $actor, $decision);
        }
    }

    public function notifyDocumentSubmitted(ControlledDocument $document, User $actor): void
    {
        $document->loadMissing(['documentStatus', 'documentType']);

        $this->dispatcher->send(
            $this->recipients->currentDocumentReviewers($document),
            $actor,
            Notification::make()
                ->title('Document submitted for your review')
                ->body($this->documentLabel($document).' is waiting at the current approval step.')
                ->icon(Heroicon::PaperAirplane)
                ->info()
                ->actions($this->dispatcher->openActions($this->documentUrl($document), 'Review')),
        );
    }

    public function notifyDocumentDecision(
        SopApproval $approval,
        User $actor,
        ApprovalDecisionCode $decision,
    ): void {
        $document = $approval->document()->with(['documentStatus', 'documentType', 'creator', 'owner'])->first();

        if (! $document instanceof ControlledDocument) {
            return;
        }

        match ($decision) {
            ApprovalDecisionCode::RETURNED => $this->dispatcher->send(
                $this->recipients->documentStakeholders($document),
                $actor,
                Notification::make()
                    ->title('Document returned for correction')
                    ->body($this->documentLabel($document).' was returned. Review the section comments and update the flagged sections.')
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->warning()
                    ->actions($this->dispatcher->openActions($this->documentUrl($document))),
            ),
            ApprovalDecisionCode::REJECTED => $this->dispatcher->send(
                $this->recipients->documentStakeholders($document),
                $actor,
                Notification::make()
                    ->title('Document rejected')
                    ->body($this->documentLabel($document).' was rejected during approval.')
                    ->icon(Heroicon::XCircle)
                    ->danger()
                    ->actions($this->dispatcher->openActions($this->documentUrl($document))),
            ),
            ApprovalDecisionCode::APPROVED => $this->notifyDocumentApproved($document, $actor),
            default => null,
        };
    }

    public function notifySectionCommentAdded(
        ControlledDocumentSectionReviewComment $comment,
        User $actor,
    ): void {
        $comment->loadMissing(['document.documentType', 'section']);
        $document = $comment->document;
        $section = $comment->section;

        if (! $document instanceof ControlledDocument || ! $section instanceof ControlledDocumentSection) {
            return;
        }

        $this->dispatcher->send(
            $this->recipients->documentStakeholders($document),
            $actor,
            Notification::make()
                ->title('Reviewer commented on '.$section->title)
                ->body($actor->name.' asked for a change in '.$this->documentLabel($document).': '.$comment->body)
                ->icon(Heroicon::ChatBubbleLeftEllipsis)
                ->warning()
                ->actions($this->dispatcher->openActions($this->documentUrl($document))),
        );
    }

    public function notifySectionCommentResolved(
        ControlledDocumentSectionReviewComment $comment,
        User $actor,
    ): void {
        $comment->loadMissing(['document.documentType', 'section', 'author']);
        $document = $comment->document;
        $section = $comment->section;
        $reviewer = $comment->author;

        if (
            ! $document instanceof ControlledDocument
            || ! $section instanceof ControlledDocumentSection
            || ! $reviewer instanceof User
        ) {
            return;
        }

        $this->dispatcher->send(
            collect([$reviewer]),
            $actor,
            Notification::make()
                ->title('Maker addressed your comment on '.$section->title)
                ->body($actor->name.' marked your comment as addressed on '.$this->documentLabel($document).'.')
                ->icon(Heroicon::Check)
                ->success()
                ->actions($this->dispatcher->openActions($this->documentUrl($document))),
        );
    }

    public function notifyTemplateSubmitted(DocumentTemplateVersion $version, User $actor): void
    {
        $version->loadMissing('template');
        $reviewers = $this->recipients->currentTemplateReviewers($version);
        $instance = $this->recipients->currentTemplateApproval($version);
        $url = $instance instanceof DocumentTemplateApprovalInstance
            ? DocumentTemplateApprovalInstanceResource::getUrl('view', ['record' => $instance])
            : DocumentTemplateResource::getUrl('view', ['record' => $version->template]);

        $this->dispatcher->send(
            $reviewers,
            $actor,
            Notification::make()
                ->title('Template submitted for your review')
                ->body($this->templateLabel($version).' is waiting at the current approval step.')
                ->icon(Heroicon::PaperAirplane)
                ->info()
                ->actions($this->dispatcher->openActions($url, 'Review')),
        );
    }

    public function notifyTemplateDecision(
        DocumentTemplateApprovalInstance $instance,
        User $actor,
        ApprovalDecisionCode $decision,
    ): void {
        $instance->loadMissing('templateVersion.template');
        $version = $instance->templateVersion;

        if (! $version instanceof DocumentTemplateVersion) {
            return;
        }

        match ($decision) {
            ApprovalDecisionCode::RETURNED => $this->dispatcher->send(
                $this->recipients->templateStakeholders($version),
                $actor,
                Notification::make()
                    ->title('Template returned for correction')
                    ->body($this->templateLabel($version).' was returned to draft.')
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->warning()
                    ->actions($this->dispatcher->openActions(
                        DocumentTemplateResource::getUrl('view', ['record' => $version->template])
                    )),
            ),
            ApprovalDecisionCode::REJECTED => $this->dispatcher->send(
                $this->recipients->templateStakeholders($version),
                $actor,
                Notification::make()
                    ->title('Template rejected')
                    ->body($this->templateLabel($version).' was rejected during approval.')
                    ->icon(Heroicon::XCircle)
                    ->danger()
                    ->actions($this->dispatcher->openActions(
                        DocumentTemplateResource::getUrl('view', ['record' => $version->template])
                    )),
            ),
            ApprovalDecisionCode::APPROVED => $this->notifyTemplateApproved($version, $actor),
            default => null,
        };
    }

    private function notifyDocumentApproved(ControlledDocument $document, User $actor): void
    {
        $document->refresh()->loadMissing(['documentStatus', 'documentType']);

        if ($document->documentStatus?->hasCode(DocumentStatus::EFFECTIVE)
            || $document->documentStatus?->hasCode(DocumentStatus::APPROVED)) {
            $this->dispatcher->send(
                $this->recipients->documentStakeholders($document),
                $actor,
                Notification::make()
                    ->title('Document approved')
                    ->body($this->documentLabel($document).' completed approval.')
                    ->icon(Heroicon::CheckBadge)
                    ->success()
                    ->actions($this->dispatcher->openActions($this->documentUrl($document))),
            );

            return;
        }

        $this->dispatcher->send(
            $this->recipients->currentDocumentReviewers($document),
            $actor,
            Notification::make()
                ->title('Document is waiting for your approval')
                ->body($this->documentLabel($document).' is now at your workflow step.')
                ->icon(Heroicon::CheckBadge)
                ->info()
                ->actions($this->dispatcher->openActions($this->documentUrl($document), 'Review')),
        );
    }

    private function notifyTemplateApproved(DocumentTemplateVersion $version, User $actor): void
    {
        $version->refresh()->loadMissing('template');
        $next = $this->recipients->currentTemplateApproval($version);

        if ($next instanceof DocumentTemplateApprovalInstance) {
            $this->dispatcher->send(
                $this->recipients->currentTemplateReviewers($version),
                $actor,
                Notification::make()
                    ->title('Template is waiting for your approval')
                    ->body($this->templateLabel($version).' is now at your workflow step.')
                    ->icon(Heroicon::CheckBadge)
                    ->info()
                    ->actions($this->dispatcher->openActions(
                        DocumentTemplateApprovalInstanceResource::getUrl('view', ['record' => $next]),
                        'Review',
                    )),
            );

            return;
        }

        $this->dispatcher->send(
            $this->recipients->templateStakeholders($version),
            $actor,
            Notification::make()
                ->title('Template approved')
                ->body($this->templateLabel($version).' completed approval.')
                ->icon(Heroicon::CheckBadge)
                ->success()
                ->actions($this->dispatcher->openActions(
                    DocumentTemplateResource::getUrl('view', ['record' => $version->template])
                )),
        );
    }

    private function documentUrl(ControlledDocument $document): string
    {
        if ($document->isIssuableType()) {
            return LogDocumentResource::getUrl('view', ['record' => $document]);
        }

        return ControlledDocumentResource::getUrl('view', ['record' => $document]);
    }

    private function documentLabel(ControlledDocument $document): string
    {
        return trim(implode(' · ', array_filter([
            $document->document_number,
            $document->title,
        ])));
    }

    private function templateLabel(DocumentTemplateVersion $version): string
    {
        $template = $version->template;

        return trim(implode(' · ', array_filter([
            $template?->code,
            $template?->name,
            'v'.$version->version,
        ])));
    }
}
