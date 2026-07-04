<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\RelationManagers;

use App\Actions\Sop\ApproveDocumentAction;
use App\Actions\Sop\RejectDocumentAction;
use App\Actions\Sop\ReturnDocumentAction;
use App\Filament\Concerns\HandlesServiceExceptions;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ApprovalRelationManager extends RelationManager
{
    use HandlesServiceExceptions;

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
                TextColumn::make('workflowStep.approvalStepType.name')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('workflowStep.role.name')->label('Role'),
                TextColumn::make('approvalDecision.name')
                    ->label('Decision')
                    ->badge(),
                TextColumn::make('approver.name')->label('Decided By'),
                TextColumn::make('approved_at')->dateTime(),
                TextColumn::make('comments')->limit(50)->toggleable(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->schema([Textarea::make('comments')])
                    ->visible(fn ($record): bool => Auth::user()?->can('approve', $record) ?? false)
                    ->action(function ($record, array $data): void {
                        $this->runServiceAction(
                            fn () => app(ApproveDocumentAction::class)->execute(
                                $record,
                                Auth::user(),
                                $data['comments'] ?? null,
                            ),
                            failureTitle: 'Approval Failed',
                            successTitle: 'Document approved successfully.',
                        );
                    }),
                Action::make('return')
                    ->label('Return to Maker')
                    ->color('warning')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->schema([Textarea::make('comments')->required()])
                    ->visible(fn ($record): bool => Auth::user()?->can('approve', $record) ?? false)
                    ->action(function ($record, array $data): void {
                        $this->runServiceAction(
                            fn () => app(ReturnDocumentAction::class)->execute(
                                $record,
                                Auth::user(),
                                $data['comments'] ?? null,
                            ),
                            failureTitle: 'Return Failed',
                            successTitle: 'Document returned to maker.',
                        );
                    }),
                Action::make('reject')
                    ->color('danger')
                    ->schema([Textarea::make('comments')->required()])
                    ->visible(fn ($record): bool => Auth::user()?->can('approve', $record) ?? false)
                    ->action(function ($record, array $data): void {
                        $this->runServiceAction(
                            fn () => app(RejectDocumentAction::class)->execute(
                                $record,
                                Auth::user(),
                                $data['comments'] ?? null,
                            ),
                            failureTitle: 'Reject Failed',
                            successTitle: 'Document rejected.',
                        );
                    }),
            ]);
    }
}
