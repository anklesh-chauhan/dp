<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentIssuance;
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

    public function generateIssuanceNumber(ControlledDocument $document, int $copyNumber): string
    {
        return sprintf('%s-C%02d', $document->document_number, $copyNumber);
    }

    public function nextCopyNumber(ControlledDocument $document): int
    {
        $latestCopy = DocumentIssuance::query()
            ->where('document_id', $document->id)
            ->max('copy_number');

        $copyNumber = ($latestCopy ?? 0) + 1;

        while (DocumentIssuance::query()
            ->where('issuance_number', $this->generateIssuanceNumber($document, $copyNumber))
            ->exists()) {
            $copyNumber++;
        }

        return $copyNumber;
    }

    public function generateWatermarkCode(ControlledDocument $document, int $copyNumber): string
    {
        return strtoupper(sprintf('CC-%s-%02d', Str::replace('-', '', $document->document_number), $copyNumber));
    }
}
