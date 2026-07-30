<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\QMS\Models\ChangeControl;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Models\ReportTemplate;
use Illuminate\Http\Request;

class ChangeControlReportController extends Controller
{
    public function __invoke(Request $request, ChangeControl $changeControl): mixed
    {
        $template = ReportTemplate::query()
            ->active()
            ->where('scope', ReportScope::ChangeControl)
            ->where('format', ReportFormat::Pdf)
            ->whereKey($request->integer('template'))
            ->firstOrFail();

        $changeControl->load([
            'department',
            'requester',
            'owner',
            'documentImpacts.sourceDocument',
            'documentImpacts.resultDocument',
            'auditEvents.actor',
        ]);

        return view('reports.change-control', [
            'changeControl' => $changeControl,
            'template' => $template,
            'enabledFields' => $template->enabledFieldKeys(),
        ]);
    }
}
