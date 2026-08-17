<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScheduleMGapAssessments\RelationManagers;

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Models\ScheduleMGapItem;
use App\Domain\QMS\Services\ScheduleMGapItemService;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Clause Items';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('part_code')->label('Part')->sortable(),
                TextColumn::make('clause_ref')->label('Clause')->searchable()->sortable(),
                TextColumn::make('clause_title')->limit(40)->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('due_at')->dateTime()->placeholder('—'),
                TextColumn::make('closed_at')->dateTime()->placeholder('—'),
            ])
            ->filters([SelectFilter::make('status')->options(ScheduleMGapItemStatus::class)])
            ->defaultSort('clause_ref')
            ->recordActions([
                Action::make('updateStatus')
                    ->label('Update Status')
                    ->visible(fn (): bool => $this->canUpdateItems())
                    ->schema([
                        Select::make('status')
                            ->options(ScheduleMGapItemStatus::class)
                            ->required(),
                        Textarea::make('evidence_notes')->rows(3),
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload(),
                        DateTimePicker::make('due_at'),
                        ApprovalNarrativeTextarea::decisionRationale(
                            name: 'reason',
                            label: 'Update reason',
                            helperText: 'Document why the clause status or evidence is changing.',
                            context: fn (ScheduleMGapItem $record): array => [
                                'record_type' => 'Schedule M gap item update',
                                'subject' => $record->clause_ref,
                                'decision' => 'Update clause status',
                            ],
                        ),
                    ])
                    ->fillForm(fn (ScheduleMGapItem $record): array => [
                        'status' => $record->status->value,
                        'evidence_notes' => $record->evidence_notes,
                        'owner_id' => $record->owner_id,
                        'due_at' => $record->due_at,
                    ])
                    ->action(function (array $data, ScheduleMGapItem $record): void {
                        /** @var User $user */
                        $user = auth()->user();

                        app(ScheduleMGapItemService::class)->updateStatus(
                            $record,
                            $user,
                            $data['reason'],
                            [
                                'status' => $data['status'],
                                'evidence_notes' => $data['evidence_notes'] ?? null,
                                'owner_id' => $data['owner_id'] ?? null,
                                'due_at' => $data['due_at'] ?? null,
                            ],
                        );

                        Notification::make()->success()->title('Gap item updated')->send();
                    }),
            ]);
    }

    public function isReadOnly(): bool
    {
        return ! $this->canUpdateItems();
    }

    private function canUpdateItems(): bool
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof ScheduleMGapAssessment) {
            return false;
        }

        return in_array($owner->status, [
            ScheduleMGapAssessmentStatus::Draft,
            ScheduleMGapAssessmentStatus::InProgress,
            ScheduleMGapAssessmentStatus::UnderReview,
        ], true)
            && ((bool) auth()->user()?->can('Conduct:ScheduleMGapAssessment')
                || (bool) auth()->user()?->can('Update:ScheduleMGapAssessment'));
    }
}
