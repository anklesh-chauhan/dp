<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\RelationManagers;

use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChangeHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'versionHistory';

    protected static ?string $title = 'Change History';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->columns([
                TextColumn::make('version')
                    ->label('Version')
                    ->sortable()
                    ->badge()
                    ->color(fn (ControlledDocument $record): string => $record->is($this->getOwnerRecord()) ? 'primary' : 'gray'),
                TextColumn::make('documentStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (ControlledDocument $record): string => match ($record->documentStatus?->code) {
                        DocumentStatus::DRAFT => 'gray',
                        DocumentStatus::UNDER_REVIEW => 'warning',
                        DocumentStatus::APPROVED => 'info',
                        DocumentStatus::EFFECTIVE => 'success',
                        DocumentStatus::SUPERSEDED => 'warning',
                        DocumentStatus::OBSOLETE => 'warning',
                        DocumentStatus::ARCHIVED => 'gray',
                        DocumentStatus::RETENTION_COMPLETED => 'gray',
                        DocumentStatus::DESTROYED => 'danger',
                        DocumentStatus::REJECTED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('effective_date')
                    ->date()
                    ->placeholder('—'),
                TextColumn::make('revision_reason')
                    ->label('Description of change')
                    ->state(fn (ControlledDocument $record): string => $record->changeDescription())
                    ->wrap(),
                TextColumn::make('creator.name')
                    ->label('Prepared by')
                    ->placeholder('—'),
            ])
            ->defaultSort('version')
            ->recordUrl(fn (ControlledDocument $record): string => ControlledDocumentResource::getUrl('view', ['record' => $record]));
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
