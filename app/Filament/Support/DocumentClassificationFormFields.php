<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Filament\Resources\ControlledDocuments\Pages\CreateControlledDocument;
use App\Filament\Resources\DocumentTemplates\Pages\CreateDocumentTemplate;
use App\Models\DocumentCategory;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\RegulationTag;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;

final class DocumentClassificationFormFields
{
    /**
     * @return array<int, Select|Placeholder>
     */
    public static function templateFields(): array
    {
        return [
            Select::make('category_id')
                ->label('Document Category')
                ->options(fn (): array => DocumentCategory::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->preload()
                ->required()
                ->live(),
            Select::make('document_type_id')
                ->label('Document Type')
                ->options(fn (): array => DocumentType::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set, ?int $state): void {
                    if ($state === null) {
                        $set('regulationTags', []);

                        return;
                    }

                    self::syncFieldsFromDocumentType($set, $state);
                }),
            Select::make('regulationTags')
                ->label('Regulation Tags')
                ->options(fn (): array => self::regulationTagOptions())
                ->multiple()
                ->searchable()
                ->preload()
                ->required()
                ->afterStateHydrated(function (Select $component, ?DocumentTemplate $record): void {
                    if ($record === null || filled($component->getState())) {
                        return;
                    }

                    $component->state(
                        $record->regulationTags()
                            ->pluck('regulation_tags.id')
                            ->all(),
                    );
                })
                ->dehydrated(fn ($livewire): bool => $livewire instanceof CreateDocumentTemplate)
                ->helperText('Choose the regulatory frameworks this template must comply with.')
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, Placeholder|Select>
     */
    public static function templateDerivedDisplayFields(): array
    {
        return [
            Placeholder::make('classification_category')
                ->label('Document Category')
                ->content(fn (Get $get): string => self::templateClassification($get('template_id'))['category'])
                ->visible(fn (Get $get): bool => filled($get('template_id'))),
            Placeholder::make('classification_document_type')
                ->label('Document Type')
                ->content(fn (Get $get): string => self::templateClassification($get('template_id'))['documentType'])
                ->visible(fn (Get $get): bool => filled($get('template_id'))),
            Select::make('regulationTags')
                ->label('Regulation Tags')
                ->relationship(
                    name: 'regulationTags',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->orderBy('name'),
                )
                ->multiple()
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn ($livewire, Get $get): bool => $livewire instanceof CreateControlledDocument && filled($get('template_id')))
                ->dehydrated(fn ($livewire): bool => $livewire instanceof CreateControlledDocument)
                ->helperText('Choose the regulatory frameworks that apply to this document.'),
        ];
    }

    /**
     * @return array<int, TextEntry>
     */
    public static function templateRegulationTagDisplayEntries(): array
    {
        return [
            TextEntry::make('regulationTags.name')
                ->label('Regulation Tags')
                ->badge()
                ->placeholder('None selected'),
        ];
    }

    /**
     * Apply the default regulation tags associated with the chosen document type.
     */
    public static function syncFieldsFromDocumentType(Set $set, int $documentTypeId): void
    {
        $set('regulationTags', self::regulationTagIdsForDocumentType($documentTypeId));
    }

    /**
     * @return array<int, string>
     */
    public static function regulationTagOptions(): array
    {
        return RegulationTag::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int>
     */
    public static function regulationTagIdsForDocumentType(?int $documentTypeId): array
    {
        if ($documentTypeId === null) {
            return [];
        }

        return DocumentType::query()
            ->with('regulationTags')
            ->find($documentTypeId)
            ?->regulationTags
            ->pluck('id')
            ->all() ?? [];
    }

    /**
     * @return array<int>
     */
    public static function regulationTagIdsForTemplate(mixed $templateId): array
    {
        if (blank($templateId)) {
            return [];
        }

        return DocumentTemplate::query()
            ->with('regulationTags')
            ->find((int) $templateId)
            ?->regulationTags
            ->pluck('id')
            ->all() ?? [];
    }

    public static function regulationTagsLabel(?int $documentTypeId): string
    {
        if ($documentTypeId === null) {
            return '—';
        }

        $tags = DocumentType::query()
            ->with('regulationTags')
            ->find($documentTypeId)
            ?->regulationTags
            ->pluck('name')
            ->all() ?? [];

        if ($tags === []) {
            return 'None assigned';
        }

        return implode(', ', $tags);
    }

    public static function selectedRegulationTagsLabel(mixed $templateId): string
    {
        if (blank($templateId)) {
            return '—';
        }

        $tags = DocumentTemplate::query()
            ->with('regulationTags')
            ->find((int) $templateId)
            ?->regulationTags
            ->pluck('name')
            ->all() ?? [];

        if ($tags === []) {
            return 'None selected';
        }

        return implode(', ', $tags);
    }

    /**
     * @return array{category: string, documentType: string, regulationTags: string}
     */
    public static function templateClassification(mixed $templateId): array
    {
        $empty = [
            'category' => '—',
            'documentType' => '—',
            'regulationTags' => '—',
        ];

        if (blank($templateId)) {
            return $empty;
        }

        $template = DocumentTemplate::query()
            ->with(['category', 'documentType', 'regulationTags'])
            ->find((int) $templateId);

        if ($template === null) {
            return $empty;
        }

        $selectedTags = $template->regulationTags->pluck('name')->all();

        return [
            'category' => $template->category?->name ?? '—',
            'documentType' => $template->documentType !== null
                ? "{$template->documentType->name} ({$template->documentType->code})"
                : '—',
            'regulationTags' => $selectedTags === []
                ? 'None selected'
                : implode(', ', $selectedTags),
        ];
    }
}
