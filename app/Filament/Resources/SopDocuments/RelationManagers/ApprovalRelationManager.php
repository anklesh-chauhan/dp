<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\RelationManagers;

use App\Actions\Sop\ApproveDocumentAction;
use App\Actions\Sop\RejectDocumentAction;
use App\Enums\ApprovalDecision;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ApprovalRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('workflowStep.step_no')->label('Step')->sortable(),
                TextColumn::make('workflowStep.approval_type')->label('Type')->badge(),
                TextColumn::make('workflowStep.role.name')->label('Role'),
                TextColumn::make('decision')
                    ->badge()
                    ->formatStateUsing(fn (ApprovalDecision $state): string => $state->label()),
                TextColumn::make('approver.name')->label('Approved By'),
                TextColumn::make('approved_at')->dateTime(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->schema([Textarea::make('comments')])
                    ->visible(fn ($record): bool => Auth::user()?->can('approve', $record) ?? false)
                    ->action(fn ($record, array $data): mixed => app(ApproveDocumentAction::class)->execute($record, Auth::user(), $data['comments'] ?? null)),
                Action::make('reject')
                    ->color('danger')
                    ->schema([Textarea::make('comments')->required()])
                    ->visible(fn ($record): bool => Auth::user()?->can('approve', $record) ?? false)
                    ->action(fn ($record, array $data): mixed => app(RejectDocumentAction::class)->execute($record, Auth::user(), $data['comments'] ?? null)),
            ]);
    }
}
