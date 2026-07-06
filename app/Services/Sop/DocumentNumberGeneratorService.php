<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Models\Department;
use App\Models\DocumentIssuance;
use App\Models\SopDocument;
use App\Services\NumberSeries\NumberSeriesService;
use Illuminate\Support\Str;

class DocumentNumberGeneratorService
{
    public function __construct(
        private readonly NumberSeriesService $numberSeriesService,
    ) {}

    public function generate(Department $department, string $typeCode = 'SOP'): string
    {
        return $this->numberSeriesService->generate($department, $typeCode);
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
