<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SopDocument;
use Illuminate\Contracts\View\View;

class SopDocumentPrintController extends Controller
{
    public function __invoke(SopDocument $sopDocument): View
    {
        $sopDocument->load([
            'approvals.approver',
            'approvals.workflowStep',
            'creator',
            'department',
            'owner',
            'sections',
            'template',
            'variables',
        ]);

        return view('sop-documents.print', [
            'document' => $sopDocument,
        ]);
    }
}
