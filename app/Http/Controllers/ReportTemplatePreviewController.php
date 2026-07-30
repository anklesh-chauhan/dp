<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Reporting\Enums\ReportFormat;
use App\Models\ReportTemplate;
use Illuminate\Contracts\View\View;

class ReportTemplatePreviewController extends Controller
{
    public function __invoke(ReportTemplate $reportTemplate): View
    {
        abort_unless($reportTemplate->format === ReportFormat::Pdf, 422, 'Only printable templates have a visual preview.');

        return view('reports.template-preview', [
            'reportTemplate' => $reportTemplate,
            'pageSettings' => $reportTemplate->printPageSettings(),
            'headerZones' => $reportTemplate->printHeaderZones(),
            'footerZones' => $reportTemplate->printFooterZones(),
        ]);
    }
}
