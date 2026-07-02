<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentIssuances;

use App\Actions\Sop\DestroyIssuanceAction;
use App\Actions\Sop\RecallIssuanceAction;
use App\Enums\IssuanceStatus;
use App\Filament\Resources\LogDocuments\LogDocumentResource;
use App\Models\DocumentIssuance;
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

class DocumentIssuanceResource extends Resource
{
    protected static ?string $model = DocumentIssuance::class;

    protected static ?string $navigationLabel = 'Issuance Register';

    protected static ?string $modelLabel = 'Issuance';

    protected static ?string $pluralModelLabel = 'Issuances';

    protected static ?int $navigationSort = 2;

    protected static string|UnitEnum|null $navigationGroup = 'Document Issuance';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('issuance_number')->searchable()->sortable(),
                TextColumn::make('document.document_number')->label('Document #')->searchable(),
                TextColumn::make('document.title')->label('Title')->limit(30),
                TextColumn::make('document.referenced_sop_number')->label('Referenced SOP'),
                TextColumn::make('copy_number')->label('Copy #')->sortable(),
                TextColumn::make('watermark_code')->toggleable(),
                TextColumn::make('issuedToUser.name')->label('Issued To')->placeholder('—'),
                TextColumn::make('issuer.name')->label('Issued By'),
                TextColumn::make('issued_at')->dateTime()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (IssuanceStatus $state): string => $state->label())
                    ->color(fn (IssuanceStatus $state): string => match ($state) {
                        IssuanceStatus::Active => 'success',
                        IssuanceStatus::Recalled => 'warning',
                        IssuanceStatus::Destroyed => 'danger',
                    }),
            ])
            ->defaultSort('issued_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(IssuanceStatus::options()),
            ])
            ->recordActions([
                Action::make('viewDocument')
                    ->label('View Document')
                    ->icon(Heroicon::Eye)
                    ->url(fn (DocumentIssuance $record): string => LogDocumentResource::getUrl('view', ['record' => $record->document_id])),
                Action::make('printCopy')
                    ->label('Print')
                    ->icon(Heroicon::Printer)
                    ->url(fn (DocumentIssuance $record): string => route('sop-documents.print', [
                        'sopDocument' => $record->document_id,
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
                    ->action(fn (DocumentIssuance $record, array $data): mixed => app(RecallIssuanceAction::class)->execute(
                        $record,
                        Auth::user(),
                        $data['recall_reason'],
                    )),
                Action::make('destroyCopy')
                    ->label('Destroy')
                    ->color('danger')
                    ->schema([Textarea::make('destroy_reason')->required()])
                    ->visible(fn (DocumentIssuance $record): bool => $record->status !== IssuanceStatus::Destroyed
                        && (Auth::user()?->can('destroyCopy', $record) ?? false))
                    ->action(fn (DocumentIssuance $record, array $data): mixed => app(DestroyIssuanceAction::class)->execute(
                        $record,
                        Auth::user(),
                        $data['destroy_reason'],
                    )),
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
            ->with(['document', 'issuedToUser', 'issuer']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
