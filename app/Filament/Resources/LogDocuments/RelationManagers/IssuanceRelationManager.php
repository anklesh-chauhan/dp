<?php

declare(strict_types=1);

namespace App\Filament\Resources\LogDocuments\RelationManagers;

use App\Actions\Sop\DestroyIssuanceAction;
use App\Actions\Sop\RecallIssuanceAction;
use App\Enums\IssuanceStatus;
use App\Models\DocumentIssuance;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class IssuanceRelationManager extends RelationManager
{
    protected static string $relationship = 'issuances';

    protected static ?string $title = 'Controlled Copy Issuance Register';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('issuance_number')->searchable()->sortable(),
                TextColumn::make('copy_number')->label('Copy #')->sortable(),
                TextColumn::make('watermark_code')->label('Watermark'),
                TextColumn::make('issuedToUser.name')->label('Issued To User')->placeholder('—'),
                TextColumn::make('issuedToDepartment.name')->label('Issued To Dept.')->placeholder('—'),
                TextColumn::make('issued_to_location')->label('Location')->placeholder('—'),
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
            ->recordActions([
                Action::make('printCopy')
                    ->label('Print Copy')
                    ->icon(Heroicon::Printer)
                    ->url(fn (DocumentIssuance $record): string => route('sop-documents.print', [
                        'sopDocument' => $record->document_id,
                        'issuance' => $record->id,
                    ]))
                    ->openUrlInNewTab()
                    ->visible(fn (DocumentIssuance $record): bool => $record->isActive()),
                Action::make('recall')
                    ->label('Recall')
                    ->icon(Heroicon::ArrowUturnLeft)
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
                    ->label('Destroy Copy')
                    ->icon(Heroicon::Trash)
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
}
