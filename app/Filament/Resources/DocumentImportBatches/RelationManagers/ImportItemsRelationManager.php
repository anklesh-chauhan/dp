<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentImportBatches\RelationManagers;

use App\Data\ControlledDocumentData;
use App\Domain\DMS\Actions\CreateDocumentFromTemplateAction;
use App\Jobs\CreateControlledDocumentFromImportItemJob;
use App\Models\ControlledDocument;
use App\Models\DocumentImportItem;
use App\Models\DocumentTemplate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImportItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Imported Files';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')->label('File')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('suggested_number')->label('Suggested Number')
                    ->state(fn (DocumentImportItem $record): ?string => $record->metadata['document_number'] ?? null),
                TextColumn::make('suggested_title')->label('Suggested Title')
                    ->state(fn (DocumentImportItem $record): ?string => $record->metadata['title'] ?? null),
                TextColumn::make('originalArtifact.sha256')->label('SHA-256')->limit(16),
                TextColumn::make('controlledDocument.document_number')->label('Controlled Document')->placeholder('Not created'),
            ])
            ->recordActions([
                Action::make('createControlledDocument')
                    ->label('Create Controlled Document')
                    ->visible(fn (DocumentImportItem $record): bool => $record->status === 'completed' && $record->controlled_document_id === null)
                    ->schema([
                        Select::make('template_id')
                            ->label('Template')
                            ->options(fn (): array => DocumentTemplate::query()->whereHas('publishedVersion')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('document_number')->default(fn (DocumentImportItem $record): ?string => $record->metadata['document_number'] ?? null)->required(),
                        TextInput::make('title')->default(fn (DocumentImportItem $record): string => $record->metadata['title'] ?? pathinfo($record->original_name, PATHINFO_FILENAME))->required(),
                        Select::make('owner_id')->default(fn (): ?int => auth()->id())->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->required(),
                        DatePicker::make('effective_date')->default(fn (DocumentImportItem $record): ?string => self::metadataDate($record, 'effective_date')),
                        DatePicker::make('review_date')->default(fn (DocumentImportItem $record): ?string => self::metadataDate($record, 'review_date')),
                    ])
                    ->action(function (DocumentImportItem $record, array $data): void {
                        $versionId = DocumentTemplate::query()->findOrFail((int) $data['template_id'])->publishedVersion()->value('id');
                        $document = app(CreateDocumentFromTemplateAction::class)->execute(new ControlledDocumentData(
                            templateId: (int) $data['template_id'],
                            title: (string) $data['title'],
                            ownerId: (int) $data['owner_id'],
                            createdBy: (int) auth()->id(),
                            templateVersionId: $versionId === null ? null : (int) $versionId,
                            documentNumber: (string) $data['document_number'],
                            effectiveDate: filled($data['effective_date'] ?? null) ? now()->parse($data['effective_date']) : null,
                            reviewDate: filled($data['review_date'] ?? null) ? now()->parse($data['review_date']) : null,
                        ));
                        $record->update(['controlled_document_id' => $document->getKey()]);
                        $record->originalArtifact?->linkToControlledDocument($document);
                    }),
            ])
            ->headerActions([
                Action::make('createControlledDocumentsInBulk')
                    ->label('Create Controlled Documents in Bulk')
                    ->icon(Heroicon::DocumentDuplicate)
                    ->schema([
                        Select::make('template_id')
                            ->label('Published Template')
                            ->options(fn (): array => DocumentTemplate::query()->whereHas('publishedVersion')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        Select::make('default_owner_id')
                            ->label('Default Owner')
                            ->default(fn (): ?int => auth()->id())
                            ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $items = $this->getRelationship()->where('status', 'completed')->whereNull('controlled_document_id')->get();
                        $queued = 0;
                        $skipped = 0;
                        $seenNumbers = [];
                        foreach ($items as $item) {
                            $documentNumber = (string) ($item->metadata['document_number'] ?? 'IMPORTED-'.$item->getKey());
                            if (isset($seenNumbers[$documentNumber]) || ControlledDocument::query()->where('document_number', $documentNumber)->exists()) {
                                $skipped++;

                                continue;
                            }
                            $seenNumbers[$documentNumber] = true;
                            CreateControlledDocumentFromImportItemJob::dispatch($item->getKey(), (int) $data['template_id'], (int) $data['default_owner_id']);
                            $queued++;
                        }
                        Notification::make()
                            ->success()
                            ->title("Queued {$queued} controlled documents")
                            ->body($skipped > 0 ? "Skipped {$skipped} duplicate document number(s)." : null)
                            ->send();
                    }),
            ]);
    }

    private static function metadataDate(DocumentImportItem $record, string $key): ?string
    {
        $value = $record->metadata[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'm/d/Y'] as $format) {
            try {
                return CarbonImmutable::createFromFormat($format, trim($value))->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
