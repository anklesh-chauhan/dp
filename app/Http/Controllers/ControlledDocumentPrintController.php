<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Shared\Services\AuditLogService;
use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\SopAuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ControlledDocumentPrintController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function __invoke(Request $request, ControlledDocument $controlledDocument): View
    {
        $issuance = null;

        if ($request->filled('issuance')) {
            $issuance = DocumentIssuance::query()
                ->whereKey($request->integer('issuance'))
                ->where('document_id', $controlledDocument->id)
                ->firstOrFail();
        }

        if (! $controlledDocument->canBePrinted($issuance)) {
            $message = $controlledDocument->isIssuableType()
                ? 'This controlled document must be issued before printing. Open DMS → Issuance → Log Documents, issue a controlled copy, then select Print Copy from the issuance register.'
                : 'Only approved or effective documents can be printed.';

            throw new AccessDeniedHttpException($message);
        }

        $controlledDocument->load([
            'approvals.approver',
            'approvals.workflowStep.department',
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
            document: $controlledDocument,
        );

        return view('controlled-documents.print', [
            'document' => $controlledDocument,
            'issuance' => $issuance,
        ]);
    }
}
