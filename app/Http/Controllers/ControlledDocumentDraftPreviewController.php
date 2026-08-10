<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\DMS\Services\ControlledDocumentPrintPreviewService;
use App\Models\ControlledDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ControlledDocumentDraftPreviewController extends Controller
{
    public function __construct(
        private readonly ControlledDocumentPrintPreviewService $previewService,
    ) {}

    public function __invoke(Request $request, ControlledDocument $controlledDocument): View
    {
        return view(
            'controlled-documents.print',
            $this->previewService->viewData(
                $controlledDocument,
                $request->filled('template') ? $request->integer('template') : null,
            ),
        );
    }
}
