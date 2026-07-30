<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Services\TabularReportExporter;
use App\Models\ControlledDocument;
use App\Models\ReportTemplate;
use Illuminate\Http\Request;

class DocumentDistributionReportController extends Controller
{
    public function __construct(private readonly TabularReportExporter $exporter) {}

    public function __invoke(Request $request): mixed
    {
        $template = ReportTemplate::query()
            ->active()
            ->where('scope', ReportScope::DocumentDistribution)
            ->whereKey($request->integer('template'))
            ->firstOrFail();

        $documents = ControlledDocument::query()
            ->with(['department', 'documentStatus', 'owner'])
            ->withCount('activeIssuances')
            ->orderBy('document_number');

        if ($template->format === ReportFormat::Pdf) {
            return view('reports.document-distribution', [
                'documents' => $documents->get(),
                'template' => $template,
                'enabledFields' => $template->enabledFieldKeys(),
            ]);
        }

        return $this->exporter->download(
            $template,
            $documents->lazyById(),
            'document-distribution-'.now()->format('Y-m-d-His'),
        );
    }
}
