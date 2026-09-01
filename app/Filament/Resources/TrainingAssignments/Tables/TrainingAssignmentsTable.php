<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingAssignments\Tables;

use App\Domain\TMS\Enums\TrainingAssignmentSource;
use App\Domain\TMS\Models\TrainingAssignment;
use App\Filament\Support\MyTrainingQueueService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TrainingAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->where('source_type', TrainingAssignmentSource::ControlledDocument)
                    ->with([
                        'user',
                        'assignedBy',
                        'controlledDocument.documentStatus',
                        'controlledDocument.documentType',
                        'controlledDocument.department',
                    ]),
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Trainee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('controlledDocument.document_number')
                    ->label('Document')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('controlledDocument.title')
                    ->label('Title')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('controlledDocument.department.name')
                    ->label('Department')
                    ->placeholder('—'),
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
                    ->state(fn (TrainingAssignment $record): string => $record->isCompleted()
                        ? 'Completed'
                        : 'Pending')
                    ->color(fn (TrainingAssignment $record): string => $record->isCompleted()
                        ? 'success'
                        : 'warning'),
                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'pending' => $query->whereNull('completed_at'),
                            'completed' => $query->whereNotNull('completed_at'),
                            default => $query,
                        };
                    }),
            ])
            ->defaultSort('assigned_at', 'desc')
            ->recordUrl(
                fn (TrainingAssignment $record): string => app(MyTrainingQueueService::class)
                    ->documentViewUrl($record->controlledDocument),
            )
            ->emptyStateHeading('No training assignments')
            ->emptyStateDescription('Document training assignments appear here once coordinators assign trainees from approved controlled documents.');
    }
}
