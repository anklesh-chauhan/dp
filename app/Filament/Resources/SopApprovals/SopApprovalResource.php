<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopApprovals;

use App\Actions\Sop\ApproveDocumentAction;
use App\Actions\Sop\RejectDocumentAction;
use App\Actions\Sop\ReturnDocumentAction;
use App\Enums\ApprovalDecision;
use App\Enums\ApprovalStepType;
use App\Enums\DocumentStatus;
use App\Enums\SopRole;
use App\Filament\Resources\SopApprovals\Pages\ListSopApprovals;
use App\Filament\Resources\SopDocuments\SopDocumentResource;
use App\Models\SopApproval;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SopApprovalResource extends Resource
{
    protected static ?string $model = SopApproval::class;

    protected static ?string $navigationLabel = 'Approval Queue';

    protected static ?string $modelLabel = 'Approval';

    protected static ?string $pluralModelLabel = 'Approvals';

    protected static ?int $navigationSort = 2;

    protected static string|UnitEnum|null $navigationGroup = 'SOP Management';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document.document_number')
                    ->label('Document #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('document.title')
                    ->label('Title')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('document.department.name')
                    ->label('Department')
                    ->toggleable(),
                TextColumn::make('document.status')
                    ->label('Document Status')
                    ->badge()
                    ->formatStateUsing(fn (DocumentStatus $state): string => $state->label()),
                TextColumn::make('workflowStep.step_no')
                    ->label('Step')
                    ->sortable(),
                TextColumn::make('workflowStep.approval_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (ApprovalStepType $state): string => $state->label()),
                TextColumn::make('workflowStep.role.name')
                    ->label('Required Role'),
                TextColumn::make('decision')
                    ->badge()
                    ->formatStateUsing(fn (ApprovalDecision $state): string => $state->label())
                    ->color(fn (ApprovalDecision $state): string => match ($state) {
                        ApprovalDecision::Pending => 'warning',
                        ApprovalDecision::Approved => 'success',
                        ApprovalDecision::Rejected => 'danger',
                        ApprovalDecision::Returned => 'gray',
                    }),
                TextColumn::make('approver.name')
                    ->label('Decided By')
                    ->placeholder('—'),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('decision')
                    ->options(ApprovalDecision::options())
                    ->default(ApprovalDecision::Pending->value),
                SelectFilter::make('workflowStep.role')
                    ->relationship('workflowStep.role', 'name')
                    ->label('Role'),
            ])
            ->recordActions([
                Action::make('viewDocument')
                    ->label('View Document')
                    ->icon(Heroicon::Eye)
                    ->url(fn (SopApproval $record): string => SopDocumentResource::getUrl('view', ['record' => $record->document_id])),
                Action::make('approve')
                    ->icon(Heroicon::CheckCircle)
                    ->schema([Textarea::make('comments')])
                    ->visible(fn (SopApproval $record): bool => Auth::user()?->can('approve', $record) ?? false)
                    ->action(fn (SopApproval $record, array $data): mixed => app(ApproveDocumentAction::class)->execute($record, Auth::user(), $data['comments'] ?? null)),
                Action::make('return')
                    ->label('Return to Maker')
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->color('warning')
                    ->schema([Textarea::make('comments')->required()])
                    ->visible(fn (SopApproval $record): bool => Auth::user()?->can('approve', $record) ?? false)
                    ->action(fn (SopApproval $record, array $data): mixed => app(ReturnDocumentAction::class)->execute($record, Auth::user(), $data['comments'] ?? null)),
                Action::make('reject')
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->schema([Textarea::make('comments')->required()])
                    ->visible(fn (SopApproval $record): bool => Auth::user()?->can('approve', $record) ?? false)
                    ->action(fn (SopApproval $record, array $data): mixed => app(RejectDocumentAction::class)->execute($record, Auth::user(), $data['comments'] ?? null)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSopApprovals::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'document.department',
                'workflowStep.role',
                'approver',
            ]);

        $user = Auth::user();

        if ($user !== null && $user->department_id !== null && ! $user->hasRole(SopRole::Administrator->value)) {
            $query->whereHas('document', fn (Builder $documentQuery): Builder => $documentQuery->where('department_id', $user->department_id));
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
