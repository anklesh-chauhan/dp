<?php

declare(strict_types=1);

namespace App\Domain\DMS\Contracts;

use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\ReportTemplate;

interface ControlledDocumentPdfRenderer
{
    /** @param array<string, mixed> $organization */
    public function render(
        ControlledDocument $document,
        ReportTemplate $reportTemplate,
        ?DocumentIssuance $issuance,
        array $organization,
    ): string;
}
