<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentIssuances;

use App\Domain\DMS\Actions\DestroyIssuanceAction;
use App\Domain\DMS\Actions\RecallIssuanceAction;
use App\Filament\Resources\DocumentExecutions\DocumentExecutionResource;
use App\Filament\Resources\LogDocuments\LogDocumentResource;
use App\Filament\Support\ServiceExceptionHandler;
use App\Models\DocumentIssuance;
use App\Models\IssuanceStatus;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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

class DocumentIssuanceResource extends Resource
{
    protected static ?string $model = DocumentIssuance::class;

    protected static ?string $navigationLabel = 'Issuance Register';

    protected static ?string $modelLabel = 'Issuance';

    protected static ?string $pluralModelLabel = 'Issuances';

    protected static ?int $navigationSort = 2;

    protected static string|UnitEnum|null $navigationGroup = 'DMS';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function getNavigationBadge(): ?string
    {
        return strval(static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('issuance_number')->searchable()->sortable(),
                TextColumn::make('issuance_type')->label('Copy type')->badge(),
                TextColumn::make('document.document_number')->label('Document #')->searchable(),
                TextColumn::make('document.title')->label('Title')->limit(30),
                TextColumn::make('document.referenced_sop_number')->label('Referenced SOP'),
                TextColumn::make('copy_number')->label('Copy #')->sortable(),
                TextColumn::make('watermark_code')->toggleable(),
                TextColumn::make('issuedToUser.name')->label('Issued To')->placeholder('—'),
                TextColumn::make('issuer.name')->label('Issued By'),
                TextColumn::make('issued_at')->dateTime()->sortable(),
                TextColumn::make('issuanceStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (DocumentIssuance $record): string => match ($record->issuanceStatus?->code) {
                        IssuanceStatus::ACTIVE => 'success',
                        IssuanceStatus::RECALLED => 'warning',
                        IssuanceStatus::DESTROYED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('execution.status')->label('Execution')->badge()->placeholder('Not writable'),
            ])
            ->defaultSort('issued_at', 'desc')
            ->filters([
                SelectFilter::make('issuance_status_id')->relationship('issuanceStatus', 'name')->label('Status'),
                SelectFilter::make('issuance_type')->options([
                    DocumentIssuance::TYPE_REFERENCE => 'Reference copy',
                    DocumentIssuance::TYPE_EXECUTION => 'Writable execution record',
                ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('openExecution')
                        ->label('Open Execution Record')
                        ->icon(Heroicon::PencilSquare)
                        ->url(fn (DocumentIssuance $record): string => DocumentExecutionResource::getUrl(
                            $record->execution?->isEditable() ? 'edit' : 'view',
                            ['record' => $record->execution],
                        ))
                        ->visible(fn (DocumentIssuance $record): bool => $record->isExecution() && $record->execution !== null),
                    Action::make('viewDocument')
                        ->label('View Document')
                        ->icon(Heroicon::Eye)
                        ->url(fn (DocumentIssuance $record): string => LogDocumentResource::getUrl('view', ['record' => $record->document_id])),
                    Action::make('printCopy')
                        ->label('View Controlled Copy')
                        ->icon(Heroicon::Eye)
                        ->url(fn (DocumentIssuance $record): string => route('controlled-documents.viewer', [
                            'controlledDocument' => $record->document_id,
                            'issuance' => $record->id,
                        ]))
                        ->openUrlInNewTab()
                        ->visible(fn (DocumentIssuance $record): bool => $record->isActive()),
                    Action::make('recall')
                        ->label('Recall')
                        ->color('warning')
                        ->schema([Textarea::make('recall_reason')->required()])
                        ->visible(fn (DocumentIssuance $record): bool => $record->isActive()
                            && (Auth::user()?->can('recall', $record) ?? false))
                        ->action(fn (DocumentIssuance $record, array $data): mixed => ServiceExceptionHandler::run(
                            fn () => app(RecallIssuanceAction::class)->execute(
                                $record,
                                Auth::user(),
                                $data['recall_reason'],
                            ),
                            failureTitle: 'Recall Failed',
                            successTitle: 'Controlled copy recalled.',
                        )),
                    Action::make('destroyCopy')
                        ->label('Destroy')
                        ->color('danger')
                        ->schema([Textarea::make('destroy_reason')->required()])
                        ->visible(fn (DocumentIssuance $record): bool => ! $record->issuanceStatus?->hasCode(IssuanceStatus::DESTROYED)
                            && (Auth::user()?->can('destroyCopy', $record) ?? false))
                        ->action(fn (DocumentIssuance $record, array $data): mixed => ServiceExceptionHandler::run(
                            fn () => app(DestroyIssuanceAction::class)->execute(
                                $record,
                                Auth::user(),
                                $data['destroy_reason'],
                            ),
                            failureTitle: 'Destroy Failed',
                            successTitle: 'Controlled copy destroyed.',
                        )),
                ])->icon('heroicon-o-ellipsis-vertical')->dropdownTeleport(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentIssuances::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['document', 'issuanceStatus', 'issuedToUser', 'issuer', 'execution']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
