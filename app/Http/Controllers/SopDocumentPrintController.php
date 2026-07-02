<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DocumentIssuance;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Services\Sop\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SopDocumentPrintController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function __invoke(Request $request, SopDocument $sopDocument): View
    {
        $issuance = null;

        if ($request->filled('issuance')) {
            $issuance = DocumentIssuance::query()
                ->whereKey($request->integer('issuance'))
                ->where('document_id', $sopDocument->id)
                ->firstOrFail();
        }

        if (! $sopDocument->canBePrinted($issuance)) {
            throw new AccessDeniedHttpException('This document cannot be printed. Controlled copies require an active issuance.');
        }

        $sopDocument->load([
            'approvals.approver',
            'approvals.workflowStep',
            'creator',
            'department',
            'documentType',
            'issuances',
            'owner',
            'referencedSop',
            'sections',
            'template',
            'variables',
        ]);

        $this->auditLogService->log(
            action: SopAuditLog::ACTION_PRINTED,
            newValues: [
                'issuance_id' => $issuance?->id,
                'issuance_number' => $issuance?->issuance_number,
                'watermark_code' => $issuance?->watermark_code,
            ],
            document: $sopDocument,
        );

        return view('sop-documents.print', [
            'document' => $sopDocument,
            'issuance' => $issuance,
        ]);
    }
}
