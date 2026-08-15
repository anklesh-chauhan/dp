<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\RelationManagers;

use App\Domain\DMS\Actions\ResolveSectionReviewCommentAction;
use App\Domain\DMS\Services\ControlledDocumentSectionReviewService;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Resources\ControlledDocuments\Pages\ViewControlledDocument;
use App\Filament\Resources\LogDocuments\Pages\ViewLogDocument;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSectionReviewComment;
use App\Models\DocumentStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SectionReviewCommentsRelationManager extends RelationManager
{
    use HandlesServiceExceptions;

    protected static string $relationship = 'sectionReviewComments';

    protected static ?string $title = 'Reviewer comments';

    public static function canViewForRecord(object $ownerRecord, string $pageClass): bool
    {
        if (! $ownerRecord instanceof ControlledDocument) {
            return false;
        }

        if (! in_array($pageClass, [ViewControlledDocument::class, ViewLogDocument::class], true)) {
            return $ownerRecord->sectionReviewComments()->exists();
        }

        if ($ownerRecord->documentStatus?->hasCode(DocumentStatus::UNDER_REVIEW)) {
            return true;
        }

        return $ownerRecord->sectionReviewComments()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['section', 'author', 'resolver']))
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('section.section_order')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('section.title')
                    ->label('Section')
                    ->searchable()
                    ->wrap()
                    ->description(fn (ControlledDocumentSectionReviewComment $record): string => $record->isOpen()
                        ? 'Maker should update this section'
                        : 'Addressed'),
                TextColumn::make('body')
                    ->label('Comment')
                    ->wrap()
                    ->limit(120)
                    ->searchable(),
                TextColumn::make('author.name')
                    ->label('Reviewer')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Commented')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (ControlledDocumentSectionReviewComment $record): string => $record->isOpen()
                        ? 'Needs update'
                        : 'Addressed')
                    ->color(fn (ControlledDocumentSectionReviewComment $record): string => $record->isOpen()
                        ? 'warning'
                        : 'success'),
                TextColumn::make('resolver.name')
                    ->label('Addressed by')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('resolve')
                    ->label('Mark addressed')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark this reviewer comment as addressed?')
                    ->modalDescription('Use this after you have updated the section the reviewer flagged.')
                    ->modalSubmitActionLabel('Mark addressed')
                    ->visible(fn (ControlledDocumentSectionReviewComment $record): bool => $record->isOpen()
                        && $this->canResolveComments())
                    ->action(function (ControlledDocumentSectionReviewComment $record): void {
                        $this->runServiceAction(
                            fn () => app(ResolveSectionReviewCommentAction::class)->execute($record, Auth::user()),
                            failureTitle: 'Could not update comment',
                            successTitle: 'Comment marked as addressed',
                        );
                    }),
            ]);
    }

    private function canResolveComments(): bool
    {
        $owner = $this->getOwnerRecord();
        $user = Auth::user();

        return $owner instanceof ControlledDocument
            && $user instanceof User
            && app(ControlledDocumentSectionReviewService::class)->canResolve($owner, $user);
    }
}
