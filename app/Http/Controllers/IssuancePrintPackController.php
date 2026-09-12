<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\DMS\Services\ControlledDocumentAccessService;
use App\Domain\DMS\Services\DocumentIssuanceAccessService;
use App\Domain\DMS\Services\IssuancePrintPackService;
use App\Models\DocumentIssuance;
use App\Models\DocumentIssuanceBatch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IssuancePrintPackController extends Controller
{
    public function __construct(
        private readonly IssuancePrintPackService $printPacks,
        private readonly DocumentIssuanceAccessService $issuanceAccessService,
        private readonly ControlledDocumentAccessService $accessService,
    ) {}

    public function preview(Request $request, DocumentIssuanceBatch $issuanceBatch): View
    {
        $this->authorizePack($request, $issuanceBatch);

        if (! $issuanceBatch->isPackReady()) {
            $result = $this->printPacks->request($issuanceBatch, $request->user());
            $issuanceBatch = $result['batch'];
        }

        $ready = $issuanceBatch->isPackReady();

        return view('controlled-documents.viewer', [
            'document' => $issuanceBatch->document,
            'contentUrl' => $ready ? route('issuance-batches.print-pack', $issuanceBatch) : '',
            'printUrl' => null,
            'printPreviewUrl' => null,
            'downloadUrl' => $ready && $this->accessService->canDownload($request->user(), $issuanceBatch->document)
                ? route('issuance-batches.print-pack', $issuanceBatch)
                : null,
            'watermark' => '',
            'printMode' => true,
            'autoPrint' => true,
            'pollUrl' => $ready ? null : route('issuance-batches.print-status', $issuanceBatch),
        ]);
    }

    public function status(Request $request, DocumentIssuanceBatch $issuanceBatch): JsonResponse
    {
        $this->authorizePack($request, $issuanceBatch);

        $ready = $issuanceBatch->isPackReady();

        return response()->json([
            'status' => $issuanceBatch->pack_status,
            'ready' => $ready,
            'error' => $issuanceBatch->pack_error,
            'pdf_url' => $ready ? route('issuance-batches.print-pack', $issuanceBatch) : null,
        ]);
    }

    public function pdf(Request $request, DocumentIssuanceBatch $issuanceBatch): StreamedResponse
    {
        $this->authorizePack($request, $issuanceBatch);

        if ($issuanceBatch->isPackPending()) {
            throw new HttpException(423, 'The copies are still being prepared for print.');
        }

        if (! $issuanceBatch->isPackReady()) {
            throw new HttpException(409, $issuanceBatch->pack_error ?: 'These copies are not ready to print.');
        }

        $this->printPacks->assertIntegrity($issuanceBatch);

        return Storage::disk($issuanceBatch->pack_disk)->response(
            $issuanceBatch->pack_path,
            $issuanceBatch->pack_filename,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$issuanceBatch->pack_filename.'"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Document-SHA256' => $issuanceBatch->pack_sha256,
            ],
        );
    }

    private function authorizePack(Request $request, DocumentIssuanceBatch $issuanceBatch): void
    {
        $user = $request->user();
        $issuanceBatch->load(['document']);
        $copies = $issuanceBatch->printableCopies();

        if ($copies->isEmpty() || $copies->contains(
            fn (DocumentIssuance $issuance): bool => ! $this->issuanceAccessService->canAccess($user, $issuance),
        )) {
            throw new AccessDeniedHttpException('You do not have access to these copies.');
        }

        if (! $this->accessService->canPrint($user, $issuanceBatch->document)) {
            throw new AccessDeniedHttpException('You do not have permission to print this controlled document.');
        }
    }
}
