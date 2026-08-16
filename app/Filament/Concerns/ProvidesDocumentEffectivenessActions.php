<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Domain\DMS\Actions\MakeDocumentEffectiveAction;
use App\Domain\DMS\Services\DocumentTrainingService;
use App\Filament\Support\AssignDocumentTrainingAction;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

trait ProvidesDocumentEffectivenessActions
{
    protected function assignDocumentTrainingAction(): Action
    {
        return AssignDocumentTrainingAction::make(fn (): ControlledDocument => $this->effectivenessRecord());
    }

    protected function makeDocumentEffectiveAction(): Action
    {
        return Action::make('makeEffective')
            ->label('Make Effective')
            ->icon(Heroicon::CheckBadge)
            ->color('success')
            ->schema([
                DatePicker::make('effective_date')
                    ->label('Effective date')
                    ->helperText('This is the date operations must start following this version. It cannot be earlier than today.')
                    ->minDate(now()->startOfDay())
                    ->required(),
                Textarea::make('reason')
                    ->label('Release reason')
                    ->rows(2),
            ])
            ->fillForm(fn (): array => [
                'effective_date' => $this->effectivenessRecord()->effective_date?->toDateString()
                    ?? now()->toDateString(),
            ])
            ->modalHeading('Confirm the effective date?')
            ->modalDescription(fn (): string => $this->makeEffectiveModalDescription())
            ->modalSubmitActionLabel('Make effective')
            ->disabled(fn (): bool => ! $this->trainingGateIsSatisfied())
            ->tooltip(fn (): ?string => $this->trainingGateIsSatisfied()
                ? null
                : 'Required training must be completed before this document can be made effective.')
            ->visible(fn (): bool => $this->effectivenessRecord()->documentStatus?->hasCode(DocumentStatus::APPROVED)
                && (Auth::user()?->can('makeEffective', $this->effectivenessRecord()) ?? false))
            ->action(function (array $data): void {
                $this->runServiceAction(
                    fn () => app(MakeDocumentEffectiveAction::class)->execute(
                        $this->effectivenessRecord(),
                        Auth::user(),
                        $data['effective_date'],
                        $data['reason'] ?? null,
                    ),
                    failureTitle: 'Could not make document effective',
                    successTitle: 'Effective date confirmed',
                    successBody: 'The document becomes effective on the confirmed date. A prior effective version in the same series is superseded at that time.',
                    afterSuccess: fn () => $this->refreshFormData([
                        'document_status_id',
                        'effective_date',
                        'released_for_effectiveness_at',
                        'released_for_effectiveness_by',
                    ]),
                );
            });
    }

    protected function approvedEffectivenessSubheading(): ?string
    {
        $record = $this->effectivenessRecord();

        if (! $record->documentStatus?->hasCode(DocumentStatus::APPROVED)) {
            return null;
        }

        $training = app(DocumentTrainingService::class);

        if (! $training->isSatisfied($record)) {
            return 'Approved · Required training must be completed before Document Control can set the effective date.';
        }

        if ($record->released_for_effectiveness_at === null) {
            return $training->requiresTraining($record)
                ? 'Approved · Training complete. Document Control can confirm the effective date.'
                : 'Approved · Document Control can confirm the effective date.';
        }

        $date = $record->effective_date?->toDateString() ?? 'the confirmed date';

        return "Approved · Effective date confirmed for {$date}.";
    }

    private function makeEffectiveModalDescription(): string
    {
        $record = $this->effectivenessRecord();

        if ($record->released_for_effectiveness_at !== null) {
            return 'Update the confirmed effective date. The document stays Approved until that date, then becomes Effective.';
        }

        return 'Confirm the date this approved version becomes the current procedure. Issuance and operational use start on that date.';
    }

    private function trainingGateIsSatisfied(): bool
    {
        return app(DocumentTrainingService::class)->isSatisfied($this->effectivenessRecord());
    }

    private function effectivenessRecord(): ControlledDocument
    {
        /** @var ControlledDocument $record */
        $record = $this->record;

        return $record;
    }
}
