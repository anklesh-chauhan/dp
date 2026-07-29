<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplates\RelationManagers;

use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Models\DocumentTemplateApprovalEvent;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApprovalEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvalEvents';

    protected static ?string $title = 'Template Approval History';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reason')
            ->columns([
                TextColumn::make('templateVersion.version')
                    ->label('Version')
                    ->sortable(),
                TextColumn::make('from_status')
                    ->label('From')
                    ->formatStateUsing(fn ($state): string => $state->label())
                    ->badge(),
                TextColumn::make('to_status')
                    ->label('Decision')
                    ->formatStateUsing(fn ($state): string => $state->label())
                    ->badge(),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->placeholder('Deleted user'),
                TextColumn::make('reason')
                    ->wrap(),
                TextColumn::make('signature_status')
                    ->label('Signature')
                    ->state(fn (DocumentTemplateApprovalEvent $record): string => $record->signature_hash === null
                        ? 'Not required'
                        : (app(ElectronicSignatureVerifier::class)->isValid($record) ? 'Valid' : 'Invalid'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Valid' => 'success',
                        'Invalid' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('occurred_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}
