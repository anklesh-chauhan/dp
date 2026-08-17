<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditFindings\Pages;

use App\Domain\QMS\Enums\AuditFindingClassification;
use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\CapaType;
use App\Domain\QMS\Services\AuditFindingCapaService;
use App\Domain\QMS\Services\AuditFindingTransitionService;
use App\Filament\Resources\AuditFindings\AuditFindingResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;

final class ViewAuditFinding extends ViewRecord
{
    protected static string $resource = AuditFindingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => AuditFindingResource::canEdit($this->record)),
            $this->transitionAction(
                'requestResponse',
                'Request Response',
                AuditFindingDisposition::ResponsePending,
                [AuditFindingDisposition::Open],
                'Respond:AuditFinding',
            ),
            $this->transitionAction(
                'submitResponse',
                'Submit Response',
                AuditFindingDisposition::UnderVerification,
                [AuditFindingDisposition::ResponsePending],
                'Respond:AuditFinding',
                requireResponse: true,
            ),
            $this->transitionAction(
                'accept',
                'Accept Response',
                AuditFindingDisposition::Accepted,
                [AuditFindingDisposition::UnderVerification],
                'Verify:AuditFinding',
                'success',
                requireVerification: true,
            ),
            $this->transitionAction(
                'returnForRework',
                'Return for Rework',
                AuditFindingDisposition::ResponsePending,
                [AuditFindingDisposition::UnderVerification],
                'Verify:AuditFinding',
                'warning',
                requireVerification: true,
            ),
            $this->transitionAction(
                'reject',
                'Reject',
                AuditFindingDisposition::Rejected,
                [AuditFindingDisposition::Open, AuditFindingDisposition::UnderVerification],
                'Verify:AuditFinding',
                'danger',
                requireVerification: true,
            ),
            $this->transitionAction(
                'close',
                'Close',
                AuditFindingDisposition::Closed,
                [AuditFindingDisposition::Accepted],
                'Close:AuditFinding',
                'success',
            ),
            $this->transitionAction(
                'cancel',
                'Cancel',
                AuditFindingDisposition::Cancelled,
                [AuditFindingDisposition::Open, AuditFindingDisposition::ResponsePending],
                'Manage:AuditFinding',
                'danger',
            ),
            $this->openCapaAction(),
        ];
    }

    private function openCapaAction(): Action
    {
        return Action::make('openCapa')
            ->label('Open CAPA')
            ->color('warning')
            ->schema([
                Select::make('owner_id')
                    ->label('CAPA Owner')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('type')
                    ->options(CapaType::class)
                    ->required(),
                Textarea::make('action_plan')->required()->rows(4),
                DatePicker::make('due_at')->required(),
                DatePicker::make('effectiveness_due_at'),
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Handoff reason',
                    helperText: 'Explain why this finding requires a CAPA.',
                    context: fn (): array => [
                        'record_type' => 'Audit Finding CAPA handoff',
                        'subject' => $this->record->finding_number ?? (string) $this->record->getKey(),
                        'decision' => 'Open CAPA',
                    ],
                ),
            ])
            ->visible(fn (): bool => $this->record->classification === AuditFindingClassification::Nonconformity
                && in_array($this->record->disposition, [
                    AuditFindingDisposition::Open,
                    AuditFindingDisposition::ResponsePending,
                ], true)
                && $this->record->capas()->doesntExist()
                && (bool) auth()->user()?->can('Respond:AuditFinding')
                && (bool) auth()->user()?->can('Create:Capa'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();
                $owner = User::query()->findOrFail($data['owner_id']);

                $type = $data['type'] instanceof CapaType
                    ? $data['type']
                    : CapaType::from($data['type']);

                $capa = app(AuditFindingCapaService::class)->create(
                    $this->record,
                    $user,
                    $owner,
                    $type,
                    $data['action_plan'],
                    $data['reason'],
                    Carbon::parse($data['due_at']),
                    filled($data['effectiveness_due_at'] ?? null) ? Carbon::parse($data['effectiveness_due_at']) : null,
                );

                $this->record->refresh();
                $this->refreshFormData(['disposition']);

                Notification::make()
                    ->success()
                    ->title('CAPA opened')
                    ->body($capa->capa_number)
                    ->send();
            });
    }

    /** @param list<AuditFindingDisposition> $fromDispositions */
    private function transitionAction(
        string $name,
        string $label,
        AuditFindingDisposition $toDisposition,
        array $fromDispositions,
        string $permission,
        string $color = 'primary',
        bool $requireResponse = false,
        bool $requireVerification = false,
    ): Action {
        $schema = [];

        if ($requireResponse) {
            $schema[] = Textarea::make('response')->required()->rows(4);
        }

        if ($requireVerification) {
            $schema[] = Textarea::make('verification_notes')->required()->rows(4);
        }

        $schema[] = ApprovalNarrativeTextarea::decisionRationale(
            name: 'reason',
            label: 'Decision reason',
            helperText: 'Explain what you reviewed and why you are making this decision. This text becomes part of the signed approval record.',
            context: fn (): array => [
                'record_type' => 'Audit Finding disposition decision',
                'subject' => $this->record->finding_number ?? (string) $this->record->getKey(),
                'decision' => $label,
            ],
        );

        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema($schema)
            ->visible(fn (): bool => in_array($this->record->disposition, $fromDispositions, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toDisposition, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(AuditFindingTransitionService::class)->transition(
                    $this->record,
                    $toDisposition,
                    $user,
                    $data['reason'],
                    response: $data['response'] ?? null,
                    verificationNotes: $data['verification_notes'] ?? null,
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['disposition', 'response', 'verification_notes', 'verified_at', 'closed_at']);

                Notification::make()->success()->title("Audit Finding: {$label}")->send();
            });
    }
}
