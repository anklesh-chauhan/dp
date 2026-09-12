<?php

declare(strict_types=1);

namespace App\Domain\DMS\Contracts;

use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Support\Collection;

interface ControlledDocumentPdfRenderer
{
    /** @param array<string, mixed> $organization */
    public function render(
        ControlledDocument $document,
        ReportTemplate $reportTemplate,
        ?DocumentIssuance $issuance,
        array $organization,
        ?User $printedBy = null,
    ): string;

    /**
     * @param  Collection<int, DocumentIssuance>  $issuances
     * @param  array<string, mixed>  $organization
     */
    public function renderPack(
        ControlledDocument $document,
        ReportTemplate $reportTemplate,
        Collection $issuances,
        array $organization,
        ?User $printedBy = null,
    ): string;
}
