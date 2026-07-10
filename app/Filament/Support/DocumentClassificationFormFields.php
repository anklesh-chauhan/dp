<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\DocumentType;
use App\Models\SopTemplate;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

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
                ->relationship('category', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set): mixed => $set('document_type_id', null)),
            Select::make('document_type_id')
                ->label('Document Type')
                ->options(fn (Get $get): array => DocumentType::query()
                    ->when(
                        filled($get('category_id')),
                        fn ($query) => $query->where('category_id', $get('category_id')),
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
                        return;
                    }

                    $categoryId = DocumentType::query()->whereKey($state)->value('category_id');

                    if ($categoryId !== null) {
                        $set('category_id', $categoryId);
                    }
                }),
            Placeholder::make('regulation_tags_display')
                ->label('Regulation Tags')
                ->content(fn (Get $get): string => self::regulationTagsLabel(
                    self::resolveDocumentTypeId($get('document_type_id')),
                ))
                ->visible(fn (Get $get): bool => filled($get('document_type_id'))),
        ];
    }

    /**
     * @return array<int, Placeholder>
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
            Placeholder::make('classification_regulation_tags')
                ->label('Regulation Tags')
                ->content(fn (Get $get): string => self::templateClassification($get('template_id'))['regulationTags'])
                ->visible(fn (Get $get): bool => filled($get('template_id'))),
        ];
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
            ->with(['category', 'documentType.regulationTags'])
            ->find((int) $templateId);

        if ($template === null) {
            return $empty;
        }

        return [
            'category' => $template->category?->name ?? '—',
            'documentType' => $template->documentType !== null
                ? "{$template->documentType->name} ({$template->documentType->code})"
                : '—',
            'regulationTags' => self::regulationTagsLabel($template->document_type_id),
        ];
    }

    private static function resolveDocumentTypeId(mixed $documentTypeId): ?int
    {
        if ($documentTypeId === null || $documentTypeId === '' || $documentTypeId === 0) {
            return null;
        }

        return (int) $documentTypeId;
    }
}
