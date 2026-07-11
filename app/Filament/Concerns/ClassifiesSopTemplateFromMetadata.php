<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\Department;
use App\Models\DocumentType;
use App\Services\AI\DocumentAiClassifier;
use Filament\Schemas\Components\Utilities\Set;

trait ClassifiesSopTemplateFromMetadata
{
    private bool $isClassifyingFromMetadata = false;

    public function classifyFromMetadata(Set $set): void
    {
        if ($this->isClassifyingFromMetadata) {
            return;
        }

        $name = trim((string) ($this->data['name'] ?? ''));
        $description = trim((string) ($this->data['description'] ?? ''));
        $departmentId = $this->data['department_id'] ?? null;

        if (
            $name === ''
            || $description === ''
            || blank($departmentId)
        ) {
            return;
        }

        $departmentName = Department::query()
            ->whereKey((int) $departmentId)
            ->value('name');

        if (blank($departmentName)) {
            return;
        }

        $this->isClassifyingFromMetadata = true;

        try {
            $classification = app(DocumentAiClassifier::class)->classify(
                name: $name,
                description: $description,
                departmentName: (string) $departmentName,
            );

            $documentTypeId = $classification['document_type_id'] ?? null;

            if (blank($documentTypeId)) {
                return;
            }

            $documentType = DocumentType::query()
                ->with('regulationTags:id')
                ->find((int) $documentTypeId);

            if ($documentType === null) {
                return;
            }

            $set(
                'category_id',
                $documentType->category_id !== null
                    ? (int) $documentType->category_id
                    : null,
            );

            $set(
                'document_type_id',
                (int) $documentType->getKey(),
            );

            $set(
                'regulationTags',
                $documentType->regulationTags->modelKeys(),
            );
        } finally {
            $this->isClassifyingFromMetadata = false;
        }
    }
}
