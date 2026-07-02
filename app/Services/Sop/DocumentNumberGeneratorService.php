<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Models\Department;
use App\Models\DocumentIssuance;
use App\Models\SopDocument;
use Illuminate\Support\Str;

class DocumentNumberGeneratorService
{
    public function generate(Department $department, string $typeCode = 'SOP'): string
    {
        $prefix = sprintf('%s-%s-', Str::upper($typeCode), Str::upper($department->code));
        $latestNumber = SopDocument::query()
            ->where('document_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('document_number')
            ->map(fn (string $documentNumber): int => (int) Str::afterLast($documentNumber, '-'))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($latestNumber + 1), 5, '0', STR_PAD_LEFT);
    }

    public function generateIssuanceNumber(SopDocument $document, int $copyNumber): string
    {
        return sprintf('%s-C%02d', $document->document_number, $copyNumber);
    }

    public function nextCopyNumber(SopDocument $document): int
    {
        $latestCopy = DocumentIssuance::query()
            ->where('document_id', $document->id)
            ->lockForUpdate()
            ->max('copy_number');

        return ($latestCopy ?? 0) + 1;
    }

    public function generateWatermarkCode(SopDocument $document, int $copyNumber): string
    {
        return strtoupper(sprintf('CC-%s-%02d', Str::replace('-', '', $document->document_number), $copyNumber));
    }
}
