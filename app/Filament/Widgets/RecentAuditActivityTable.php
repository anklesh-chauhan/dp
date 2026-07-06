<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SopAuditLog;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentAuditActivityTable extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Audit Activity')
            ->description('Latest tracked actions across documents and templates.')
            ->query(fn (): Builder => SopAuditLog::query()
                ->with(['user', 'document', 'template'])
                ->latest()
                ->limit(50))
            ->columns([
                TextColumn::make('action')
                    ->label('Action')
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString())
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        in_array($state, [SopAuditLog::ACTION_APPROVED, SopAuditLog::ACTION_PUBLISHED, SopAuditLog::ACTION_VERSION_PUBLISHED], true) => 'success',
                        in_array($state, [SopAuditLog::ACTION_REJECTED, SopAuditLog::ACTION_DESTROYED, SopAuditLog::ACTION_COPY_DESTROYED], true) => 'danger',
                        in_array($state, [SopAuditLog::ACTION_RETURNED, SopAuditLog::ACTION_RECALLED], true) => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('System'),
                TextColumn::make('document.document_number')
                    ->label('Document #')
                    ->placeholder('—'),
                TextColumn::make('template.name')
                    ->label('Template')
                    ->limit(30)
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('No audit activity yet')
            ->emptyStateDescription('Actions such as approvals, issuances, and downloads will appear here.')
            ->emptyStateIcon(Heroicon::OutlinedClipboardDocumentList);
    }
}
