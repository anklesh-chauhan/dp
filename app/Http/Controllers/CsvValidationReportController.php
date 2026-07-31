<?php

namespace App\Http\Controllers;

use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Services\TabularReportExporter;
use App\Models\ReportTemplate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CsvValidationReportController extends Controller
{
    public function __construct(private readonly TabularReportExporter $exporter) {}

    public function __invoke(Request $request, CsvValidationProject $csvValidationProject): Response
    {
        $template = ReportTemplate::query()
            ->active()
            ->whereIn('scope', [
                ReportScope::CsvValidationTraceability,
                ReportScope::CsvValidationSummary,
            ])
            ->whereKey($request->integer('template'))
            ->firstOrFail();

        if ($template->scope === ReportScope::CsvValidationTraceability) {
            abort_unless(in_array($template->format, [ReportFormat::Csv, ReportFormat::Excel], true), 422);

            $requirements = $csvValidationProject->requirements()
                ->with([
                    'risks',
                    'testCases.executions.testCase',
                    'testCases.executions.deviation',
                ])
                ->orderBy('requirement_identifier')
                ->get();

            return $this->exporter->download(
                $template,
                $requirements,
                "{$csvValidationProject->project_number}-traceability",
            );
        }

        abort_unless($template->format === ReportFormat::Pdf, 422);

        $csvValidationProject->load([
            'businessOwner',
            'systemOwner',
            'qualityOwner',
            'releaser',
            'testExecutions',
            'auditEvents.actor',
        ])->loadCount(['requirements', 'testCases']);

        return response()->view('reports.csv-validation-summary', [
            'project' => $csvValidationProject,
            'template' => $template,
            'enabledFields' => $template->enabledFieldKeys(),
        ]);
    }
}
