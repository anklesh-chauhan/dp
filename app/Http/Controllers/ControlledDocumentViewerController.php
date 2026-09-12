<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\DMS\Services\ControlledDocumentAccessService;
use App\Domain\DMS\Services\DocumentIssuanceAccessService;
use App\Domain\Shared\Services\AuditLogService;
use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\DocumentOriginalArtifact;
use App\Models\SopAuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ControlledDocumentViewerController extends Controller
{
    public function __construct(
        private readonly ControlledDocumentAccessService $accessService,
        private readonly DocumentIssuanceAccessService $issuanceAccessService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function __invoke(Request $request, ControlledDocument $controlledDocument, ?DocumentOriginalArtifact $artifact = null): View
    {
        if ($artifact !== null) {
            abort_unless($artifact->controlled_document_id === $controlledDocument->getKey(), 404);
            abort_unless(strtolower((string) $artifact->mime_type) === 'application/pdf' || $artifact->preview_path !== null, 425, 'The PDF preview is still being generated.');
            if (! $this->accessService->canView($request->user(), $controlledDocument)) {
                throw new AccessDeniedHttpException('You do not have access to view this original PDF.');
            }

            $canPrint = $this->accessService->canPrint($request->user(), $controlledDocument);

            return view('controlled-documents.viewer', [
                'document' => $controlledDocument,
                'contentUrl' => route('controlled-documents.original-artifacts.view', [$controlledDocument, $artifact]),
                'printUrl' => $canPrint
                    ? route('controlled-documents.original-artifacts.print', [$controlledDocument, $artifact])
                    : null,
                'printPreviewUrl' => null,
                'downloadUrl' => $this->accessService->canDownload($request->user(), $controlledDocument)
                    ? route('controlled-documents.original-artifacts.download', [$controlledDocument, $artifact])
                    : null,
                'watermark' => $request->user()->name.' | '.$request->user()->email.' | '.now()->format('Y-m-d H:i:s T'),
                'printMode' => false,
                'autoPrint' => false,
                'pollUrl' => null,
            ]);
        }

        $issuance = $this->issuance($request, $controlledDocument);

        if ($issuance !== null && ! $this->issuanceAccessService->canAccess($request->user(), $issuance)) {
            throw new AccessDeniedHttpException('You do not have access to this controlled copy.');
        }

        if (! $controlledDocument->canBePrinted($issuance)) {
            throw new AccessDeniedHttpException('Only an approved, effective, or actively issued document can be viewed as a controlled PDF.');
        }

        if (! $this->accessService->canView($request->user(), $controlledDocument)) {
            $this->auditLogService->log(
                action: SopAuditLog::ACTION_PDF_ACCESS_DENIED,
                newValues: ['requested_action' => 'view'],
                document: $controlledDocument,
            );

            throw new AccessDeniedHttpException('You do not have access to view this controlled PDF.');
        }

        $printMode = $request->boolean('print');

        if ($printMode && ! $this->accessService->canPrint($request->user(), $controlledDocument)) {
            $this->auditLogService->log(
                action: SopAuditLog::ACTION_PDF_ACCESS_DENIED,
                newValues: ['requested_action' => 'print'],
                document: $controlledDocument,
            );

            throw new AccessDeniedHttpException('You do not have permission to print this controlled PDF.');
        }

        $parameters = array_filter([
            'controlledDocument' => $controlledDocument,
            'issuance' => $issuance?->id,
            'template' => $request->integer('template') ?: null,
        ]);

        $canPrint = $this->accessService->canPrint($request->user(), $controlledDocument);
        $printPreviewParameters = [...$parameters, 'print' => 1];

        return view('controlled-documents.viewer', [
            'document' => $controlledDocument,
            'contentUrl' => $printMode
                ? route('controlled-documents.print', $parameters)
                : route('controlled-documents.pdf-content', $parameters),
            'printUrl' => $canPrint ? route('controlled-documents.print', $parameters) : null,
            'printPreviewUrl' => $canPrint && ! $printMode
                ? route('controlled-documents.viewer', $printPreviewParameters)
                : null,
            'downloadUrl' => $this->accessService->canDownload($request->user(), $controlledDocument)
                ? route('controlled-documents.download', $parameters)
                : null,
            'watermark' => $printMode
                ? ''
                : $request->user()->name.' | '.$request->user()->email.' | '.now()->format('Y-m-d H:i:s T'),
            'printMode' => $printMode,
            'autoPrint' => $printMode,
            'pollUrl' => null,
        ]);
    }

    private function issuance(Request $request, ControlledDocument $document): ?DocumentIssuance
    {
        if (! $request->filled('issuance')) {
            return null;
        }

        return DocumentIssuance::query()
            ->whereKey($request->integer('issuance'))
            ->where('document_id', $document->id)
            ->firstOrFail();
    }
}
