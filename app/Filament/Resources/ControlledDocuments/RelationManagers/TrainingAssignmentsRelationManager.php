<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\RelationManagers;

use App\Domain\DMS\Actions\CompleteDocumentTrainingAction;
use App\Domain\DMS\Actions\RemoveDocumentTrainingAssignmentAction;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Resources\ControlledDocuments\Pages\ViewControlledDocument;
use App\Filament\Resources\LogDocuments\Pages\ViewLogDocument;
use App\Filament\Support\AssignDocumentTrainingAction;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentTrainingAssignment;
use App\Models\DocumentStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TrainingAssignmentsRelationManager extends RelationManager
{
    use HandlesServiceExceptions;

    protected static string $relationship = 'trainingAssignments';

    protected static ?string $title = 'Required training';

    public static function canViewForRecord(object $ownerRecord, string $pageClass): bool
    {
        if (! $ownerRecord instanceof ControlledDocument) {
            return false;
        }

        if (! in_array($pageClass, [ViewControlledDocument::class, ViewLogDocument::class], true)) {
            return $ownerRecord->trainingAssignments()->exists();
        }

        if ($ownerRecord->documentStatus?->hasCode(DocumentStatus::APPROVED)) {
            return true;
        }

        return $ownerRecord->trainingAssignments()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'assignedBy']))
            ->recordTitleAttribute('user.name')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Trainee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assignedBy.name')
                    ->label('Assigned by')
                    ->placeholder('—'),
                TextColumn::make('assigned_at')
                    ->label('Assigned')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (ControlledDocumentTrainingAssignment $record): string => $record->isCompleted()
                        ? 'Completed'
                        : 'Pending')
                    ->color(fn (ControlledDocumentTrainingAssignment $record): string => $record->isCompleted()
                        ? 'success'
                        : 'warning'),
                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('completion_comments')
                    ->label('Acknowledgement')
                    ->limit(80)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('assigned_at', 'desc')
            ->headerActions([
                AssignDocumentTrainingAction::make(fn (): ControlledDocument => $this->ownerDocument()),
            ])
            ->emptyStateHeading('No training assigned')
            ->emptyStateDescription('Assign the people who must complete read-and-understand before this document can become effective.')
            ->emptyStateIcon(Heroicon::AcademicCap)
            ->emptyStateActions([
                AssignDocumentTrainingAction::make(fn (): ControlledDocument => $this->ownerDocument()),
            ])
            ->recordActions([
                Action::make('completeTraining')
                    ->label('Complete training')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->schema([
                        Textarea::make('completion_comments')
                            ->label('Read and understood')
                            ->helperText('Confirm that you have read and understood this approved document.')
                            ->rows(3)
                            ->required(),
                    ])
                    ->visible(fn (ControlledDocumentTrainingAssignment $record): bool => $this->canComplete($record))
                    ->action(function (ControlledDocumentTrainingAssignment $record, array $data): void {
                        $this->runServiceAction(
                            fn () => app(CompleteDocumentTrainingAction::class)->execute(
                                $record,
                                Auth::user(),
                                $data['completion_comments'] ?? null,
                            ),
                            failureTitle: 'Could not complete training',
                            successTitle: 'Training completed',
                        );
                    }),
                Action::make('removeTraining')
                    ->label('Remove')
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ControlledDocumentTrainingAssignment $record): bool => $this->canRemove($record))
                    ->action(function (ControlledDocumentTrainingAssignment $record): void {
                        $this->runServiceAction(
                            fn () => app(RemoveDocumentTrainingAssignmentAction::class)->execute(
                                $record,
                                Auth::user(),
                            ),
                            failureTitle: 'Could not remove training assignment',
                            successTitle: 'Training assignment removed',
                        );
                    }),
            ]);
    }

    private function canAssignTraining(): bool
    {
        $document = $this->ownerDocument();
        $user = Auth::user();

        return $document->documentStatus?->hasCode(DocumentStatus::APPROVED)
            && $user instanceof User
            && $user->can('assignTraining', $document);
    }

    private function canComplete(ControlledDocumentTrainingAssignment $assignment): bool
    {
        $user = Auth::user();

        return $this->ownerDocument()->documentStatus?->hasCode(DocumentStatus::APPROVED)
            && $user instanceof User
            && (int) $assignment->user_id === (int) $user->id
            && ! $assignment->isCompleted();
    }

    private function canRemove(ControlledDocumentTrainingAssignment $assignment): bool
    {
        return $this->canAssignTraining() && ! $assignment->isCompleted();
    }

    private function ownerDocument(): ControlledDocument
    {
        /** @var ControlledDocument $document */
        $document = $this->getOwnerRecord();

        return $document;
    }
}
