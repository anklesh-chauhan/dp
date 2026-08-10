<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplates\Pages;

use App\Domain\DMS\Actions\CreateTemplateDraftRevisionAction;
use App\Domain\DMS\Actions\PublishTemplateAction;
use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\DMS\Services\TemplateApprovalService;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\ProcessesDocumentTemplateMetadataAi;
use App\Filament\Concerns\ProvidesRetentionLifecycleActions;
use App\Filament\Resources\DocumentTemplates\DocumentTemplateResource;
use App\Models\DocumentTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewDocumentTemplate extends ViewRecord
{
    use HandlesServiceExceptions;
    use ProcessesDocumentTemplateMetadataAi;
    use ProvidesRetentionLifecycleActions;

    protected static string $resource = DocumentTemplateResource::class;

    public function getSubheading(): ?string
    {
        $version = $this->draftVersion();

        if (! $version instanceof DocumentTemplateVersion) {
            if ($this->record->templateStatus?->hasCode(TemplateStatus::PUBLISHED)) {
                return 'This template is published and locked. Create a draft revision to propose changes.';
            }

            return 'No draft version is currently available.';
        }

        return match ($version->approval_status) {
            TemplateApprovalStatus::Draft => "Version {$version->version} is a draft and can be submitted for review.",
            TemplateApprovalStatus::Submitted,
            TemplateApprovalStatus::Reviewed => "Version {$version->version} is under review. Follow progress in Template Workflow Approvals below.",
            TemplateApprovalStatus::Approved => "Version {$version->version} is approved and ready to publish.",
            TemplateApprovalStatus::Rejected => "Version {$version->version} was rejected. Review the decision rationale, correct it, and resubmit.",
        };
    }

    protected function getActions(): array
    {
        return [
            Action::make('previewWithPrintTemplate')
                ->label('Preview with Print Template')
                ->icon(Heroicon::Eye)
                ->url(fn (): string => route('document-templates.versions.preview', [
                    'documentTemplate' => $this->record,
                    'documentTemplateVersion' => $this->draftVersion(),
                ]))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->draftVersion() !== null
                    && $this->record->report_template_id !== null),
            Action::make('createDraftRevision')
                ->label('Create Draft Revision')
                ->icon(Heroicon::DocumentDuplicate)
                ->color('warning')
                ->modalHeading('Create a draft revision?')
                ->modalDescription('The published version stays locked. A new draft version will be cloned from it for editing, AI updates, review, and later publishing.')
                ->modalSubmitActionLabel('Create draft revision')
                ->schema([
                    Textarea::make('change_reason')
                        ->label('Revision reason')
                        ->helperText('Describe why this published template needs a new version.')
                        ->required()
                        ->maxLength(2_000),
                ])
                ->visible(fn (): bool => Auth::user() !== null
                    && $this->record->canStartDraftRevisionBy(Auth::user()))
                ->action(function (array $data): void {
                    /** @var User $user */
                    $user = Auth::user();

                    $this->runServiceAction(
                        fn () => app(CreateTemplateDraftRevisionAction::class)->execute(
                            $this->record,
                            $user,
                            $data['change_reason'],
                        ),
                        failureTitle: 'Draft Revision Failed',
                        successTitle: 'Draft revision created',
                        successBody: 'The published version remains locked. Continue on the edit page to update the new draft.',
                        afterSuccess: function (): void {
                            $this->redirect(DocumentTemplateResource::getUrl('edit', [
                                'record' => $this->record,
                            ]));
                        },
                    );
                }),
            ...$this->getTemplateRetentionLifecycleActions(),
            $this->approvalAction(
                name: 'submitApproval',
                label: 'Submit for Review',
                from: [TemplateApprovalStatus::Draft, TemplateApprovalStatus::Rejected],
                permission: 'Submit:DocumentTemplate',
            ),
            Action::make('publish')
                ->label('Publish Approved Version')
                ->color('success')
                ->icon(Heroicon::CheckBadge)
                ->modalHeading('Publish this approved template version?')
                ->modalDescription('Publishing makes this version available for creating controlled documents. The approved version and signatures remain immutable.')
                ->schema([
                    Textarea::make('change_reason')
                        ->label('Publishing reason')
                        ->required()
                        ->maxLength(2_000),
                ])
                ->visible(fn (): bool => $this->draftVersion()?->approval_status === TemplateApprovalStatus::Approved
                    && (Auth::user()?->can('publish', $this->record) ?? false))
                ->action(function (array $data): void {
                    $this->runServiceAction(
                        fn () => app(PublishTemplateAction::class)->execute(
                            $this->record,
                            (int) Auth::id(),
                            $data['change_reason'],
                        ),
                        failureTitle: 'Publish Failed',
                        successTitle: 'Approved template version published',
                        afterSuccess: function (): void {
                            $this->record->refresh();
                        },
                    );
                }),
            EditAction::make()
                ->visible(fn (): bool => (Auth::user()?->can('update', $this->record) ?? false)
                    && Auth::user() !== null
                    && $this->record->canBeEditedBy(Auth::user())),
        ];
    }

    /**
     * @param  list<TemplateApprovalStatus>  $from
     */
    private function approvalAction(
        string $name,
        string $label,
        array $from,
        string $permission,
        string $color = 'primary',
    ): Action {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->icon(Heroicon::PaperAirplane)
            ->modalHeading('Submit this template version for review?')
            ->modalDescription('The draft will be locked for editing and sent through the configured workflow. Assigned reviewers will see each actionable step in My Approval Queue.')
            ->modalSubmitActionLabel('Submit for review')
            ->schema([
                Textarea::make('reason')
                    ->label('Submission note')
                    ->helperText('Tell reviewers what changed and what they should focus on.')
                    ->rows(4)
                    ->required()
                    ->maxLength(2_000),
            ])
            ->visible(fn (): bool => ($version = $this->draftVersion()) !== null
                && in_array($version->approval_status, $from, true)
                && (bool) Auth::user()?->can($permission))
            ->action(function (array $data) use ($label): void {
                /** @var User $user */
                $user = Auth::user();

                $this->runServiceAction(
                    fn () => app(TemplateApprovalService::class)->submit(
                        $this->record,
                        $user,
                        $data['reason'],
                        request()->ip(),
                        request()->userAgent(),
                    ),
                    failureTitle: "{$label} Failed",
                    successTitle: 'Template version submitted for review',
                    successBody: 'The draft is locked and the first actionable step is now available in the assigned reviewer’s approval queue.',
                    afterSuccess: function (): void {
                        $this->record->refresh();
                    },
                );
            });
    }

    private function draftVersion(): ?DocumentTemplateVersion
    {
        return $this->record->latestDraftVersion()->first();
    }
}
