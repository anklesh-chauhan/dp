<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DocumentType;
use App\Models\NumberSeries;

class DocumentTypeObserver
{
    public function created(DocumentType $documentType): void
    {
        NumberSeries::query()->firstOrCreate([
            'document_type_id' => $documentType->id,
        ]);
    }

    public function deleted(DocumentType $documentType): void
    {
        NumberSeries::query()
            ->where('document_type_id', $documentType->id)
            ->delete();
    }
}
