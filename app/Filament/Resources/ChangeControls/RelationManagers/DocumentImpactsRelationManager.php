<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangeControls\RelationManagers;

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Enums\DocumentImpactAction;
use App\Domain\QMS\Models\ChangeControlDocumentImpact;
use App\Domain\QMS\Services\ApprovedChangeControlDocumentRevisionService;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DocumentImpactsRelationManager extends RelationManager
{
    protected static string $relationship = 'documentImpacts';

    public function isReadOnly(): bool
    {
        return $this->ownerRecord->status !== ChangeControlStatus::Draft
            || ! (bool) auth()->user()?->can('Update:ChangeControl');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('required_action')
                    ->options(
                        collect(DocumentImpactAction::cases())
                            ->mapWithKeys(fn (DocumentImpactAction $action): array => [
                                $action->value => str($action->value)
                                    ->replace('_', ' ')
                                    ->title()
                                    ->toString(),
                            ])
                            ->all(),
                    )
                    ->required()
                    ->live(),
                Select::make('source_document_id')
                    ->relationship('sourceDocument', 'document_number')
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => $get('required_action') !== DocumentImpactAction::Create->value),
                Textarea::make('rationale')
                    ->required()
                    ->maxLength(2_000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('required_action')
            ->columns([
                TextColumn::make('required_action')->badge(),
                TextColumn::make('sourceDocument.document_number')
                    ->label('Source Document')
                    ->placeholder('New document'),
                TextColumn::make('resultDocument.document_number')
                    ->label('Result Document')
                    ->placeholder('—'),
                TextColumn::make('rationale')->wrap(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => $this->ownerRecord->status === ChangeControlStatus::Draft
                        && (bool) auth()->user()?->can('Update:ChangeControl')),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->ownerRecord->status === ChangeControlStatus::Draft
                        && (bool) auth()->user()?->can('Update:ChangeControl')),
                Action::make('implementRevision')
                    ->label('Create Draft Revision')
                    ->requiresConfirmation()
                    ->visible(fn (ChangeControlDocumentImpact $record): bool => $this->ownerRecord->status === ChangeControlStatus::Approved
                        && $record->required_action === DocumentImpactAction::Revise
                        && $record->result_document_id === null
                        && (bool) auth()->user()?->can('Implement:ChangeControl'))
                    ->action(function (ChangeControlDocumentImpact $record): void {
                        /** @var User $user */
                        $user = auth()->user();

                        app(ApprovedChangeControlDocumentRevisionService::class)
                            ->execute($record, $user);
                        $this->ownerRecord->refresh();

                        Notification::make()
                            ->success()
                            ->title('Draft document revision created')
                            ->send();
                    }),
            ]);
    }
}
