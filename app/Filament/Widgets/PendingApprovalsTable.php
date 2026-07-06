<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\SopDocuments\SopDocumentResource;
use App\Models\SopApproval;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PendingApprovalsTable extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Your Approval Queue')
            ->description('Pending approvals visible to you.')
            ->query(fn (): Builder => $this->getTableQuery())
            ->columns([
                TextColumn::make('document.document_number')
                    ->label('Document #')
                    ->searchable(),
                TextColumn::make('document.title')
                    ->label('Title')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('document.department.name')
                    ->label('Department'),
                TextColumn::make('workflowStep.step_no')
                    ->label('Step')
                    ->sortable(),
                TextColumn::make('workflowStep.approvalStepType.name')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('workflowStep.role.name')
                    ->label('Required Role'),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->recordActions([
                Action::make('viewDocument')
                    ->label('View')
                    ->icon(Heroicon::Eye)
                    ->url(fn (SopApproval $record): string => SopDocumentResource::getUrl('view', ['record' => $record->document_id])),
            ])
            ->emptyStateHeading('No pending approvals')
            ->emptyStateDescription('You are all caught up. New submissions will appear here.')
            ->emptyStateIcon(Heroicon::OutlinedCheckBadge);
    }

    /**
     * @return Builder<SopApproval>
     */
    protected function getTableQuery(): Builder
    {
        $query = SopApproval::query()
            ->pending()
            ->with([
                'document.department',
                'workflowStep.approvalStepType',
                'workflowStep.role',
            ]);

        $user = Auth::user();

        if ($user !== null) {
            $query->visibleToUser($user);
        }

        return $query;
    }
}
