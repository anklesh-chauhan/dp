<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Filament\Resources\SopDocuments\Pages\CreateSopDocument;
use App\Filament\Resources\SopTemplates\Pages\CreateSopTemplate;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\RegulationTag;
use App\Models\SopTemplate;
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
                ->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('document_type_id', null);
                    $set('regulationTags', []);
                }),
            Select::make('document_type_id')
                ->label('Document Type')
                ->options(fn (Get $get): array => DocumentType::query()
                    ->when(
                        filled($get('category_id')),
                        fn (Builder $query): Builder => $query->where('category_id', (int) $get('category_id')),
                        fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
                    )
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->disabled(fn (Get $get): bool => blank($get('category_id')))
                ->afterStateUpdated(function (Set $set, ?int $state): void {
                    if ($state === null) {
                        $set('regulationTags', []);

                        return;
                    }

                    self::syncFieldsFromDocumentType($set, $state);
                }),
            Select::make('regulationTags')
                ->label('Regulation Tags')
                ->options(fn (Get $get): array => self::regulationTagOptionsForDocumentType(
                    filled($get('document_type_id')) ? (int) $get('document_type_id') : null,
                ))
                ->multiple()
                ->searchable()
                ->preload()
                ->required()
                ->dehydrated(fn ($livewire): bool => $livewire instanceof CreateSopTemplate)
                ->visible(fn (Get $get): bool => filled($get('document_type_id')))
                ->disabled(fn (Get $get): bool => blank($get('document_type_id')))
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
                    modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                        ->when(
                            filled($get('template_id')),
                            fn (Builder $scopedQuery): Builder => $scopedQuery->whereHas(
                                'documentTypes',
                                fn (Builder $documentTypeQuery): Builder => $documentTypeQuery->whereKey(
                                    SopTemplate::query()->whereKey($get('template_id'))->value('document_type_id'),
                                ),
                            ),
                            fn (Builder $scopedQuery): Builder => $scopedQuery->whereRaw('1 = 0'),
                        )
                        ->orderBy('name'),
                )
                ->multiple()
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn ($livewire, Get $get): bool => $livewire instanceof CreateSopDocument && filled($get('template_id')))
                ->dehydrated(fn ($livewire): bool => $livewire instanceof CreateSopDocument)
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
     * Keep category and regulation tags aligned with the chosen document type.
     */
    public static function syncFieldsFromDocumentType(Set $set, int $documentTypeId): void
    {
        $categoryId = DocumentType::query()
            ->whereKey($documentTypeId)
            ->value('category_id');

        if ($categoryId !== null) {
            $set('category_id', (int) $categoryId);
        }

        $set('regulationTags', self::regulationTagIdsForDocumentType($documentTypeId));
    }

    /**
     * @return array<int, string>
     */
    public static function regulationTagOptionsForDocumentType(?int $documentTypeId): array
    {
        if ($documentTypeId === null) {
            return [];
        }

        return RegulationTag::query()
            ->whereHas(
                'documentTypes',
                fn (Builder $documentTypeQuery): Builder => $documentTypeQuery->whereKey($documentTypeId),
            )
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

        return SopTemplate::query()
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

        $tags = SopTemplate::query()
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

        $template = SopTemplate::query()
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
