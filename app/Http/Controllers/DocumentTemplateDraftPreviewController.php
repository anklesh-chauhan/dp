<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\DMS\Services\DocumentTemplatePrintPreviewService;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use Illuminate\Contracts\View\View;

class DocumentTemplateDraftPreviewController extends Controller
{
    public function __construct(
        private readonly DocumentTemplatePrintPreviewService $previewService,
    ) {}

    public function __invoke(
        DocumentTemplate $documentTemplate,
        ?DocumentTemplateVersion $documentTemplateVersion = null,
    ): View {
        return view(
            'controlled-documents.print',
            $this->previewService->viewData($documentTemplate, $documentTemplateVersion),
        );
    }
}
