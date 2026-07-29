<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplates\Pages;

use App\Domain\DMS\Actions\PublishTemplateAction;
use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\DMS\Services\TemplateApprovalService;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\ProcessesDocumentTemplateMetadataAi;
use App\Filament\Concerns\ProvidesRetentionLifecycleActions;
use App\Filament\Resources\DocumentTemplates\DocumentTemplateResource;
use App\Models\DocumentTemplateVersion;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewDocumentTemplate extends ViewRecord
{
    use HandlesServiceExceptions;
    use ProcessesDocumentTemplateMetadataAi;
    use ProvidesRetentionLifecycleActions;

    protected static string $resource = DocumentTemplateResource::class;

    protected function getActions(): array
    {
        return [
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
            ->schema([
                Textarea::make('reason')
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
                    successTitle: "Template version: {$label}",
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
