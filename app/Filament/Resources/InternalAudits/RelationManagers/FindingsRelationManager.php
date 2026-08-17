<?php

declare(strict_types=1);

namespace App\Filament\Resources\InternalAudits\RelationManagers;

use App\Filament\Resources\AuditFindings\AuditFindingResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class FindingsRelationManager extends RelationManager
{
    protected static string $relationship = 'findings';

    protected static ?string $title = 'Findings';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('finding_number')->label('Finding Number')->searchable(),
                TextColumn::make('title')->limit(40),
                TextColumn::make('severity')->badge(),
                TextColumn::make('classification')->badge(),
                TextColumn::make('disposition')->badge(),
                TextColumn::make('response_due_at')->date()->placeholder('—'),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('viewFinding')
                    ->label('View')
                    ->url(fn ($record): string => AuditFindingResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
