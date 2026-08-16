<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\DMS\Actions\AssignDocumentTrainingAction as AssignDocumentTrainingDomainAction;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class AssignDocumentTrainingAction
{
    public static function make(callable $resolveDocument): Action
    {
        return Action::make('assignTraining')
            ->label('Assign training')
            ->icon(Heroicon::UserPlus)
            ->color('primary')
            ->modalHeading('Assign required training')
            ->modalDescription('Select the people who must complete read-and-understand before this document can become effective.')
            ->modalSubmitActionLabel('Assign training')
            ->schema(fn (): array => [
                Select::make('user_ids')
                    ->label('Trainees')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => self::assignableUserOptions($resolveDocument()))
                    ->required(),
            ])
            ->authorize(fn (): bool => self::canAssign($resolveDocument()))
            ->visible(fn (): bool => self::canAssign($resolveDocument()))
            ->action(function (array $data) use ($resolveDocument): void {
                ServiceExceptionHandler::run(
                    fn () => app(AssignDocumentTrainingDomainAction::class)->execute(
                        $resolveDocument(),
                        Auth::user(),
                        $data['user_ids'] ?? [],
                    ),
                    failureTitle: 'Could not assign training',
                    successTitle: 'Training assigned',
                    successBody: 'Assigned people can now complete read-and-understand on this document.',
                );
            });
    }

    public static function canAssign(?ControlledDocument $document): bool
    {
        $user = Auth::user();

        return $document instanceof ControlledDocument
            && $document->documentStatus?->hasCode(DocumentStatus::APPROVED)
            && $user instanceof User
            && $user->can('assignTraining', $document);
    }

    /**
     * @return array<int, string>
     */
    private static function assignableUserOptions(ControlledDocument $document): array
    {
        $assignedUserIds = $document->trainingAssignments()->pluck('user_id');

        return User::query()
            ->whereKeyNot($assignedUserIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
