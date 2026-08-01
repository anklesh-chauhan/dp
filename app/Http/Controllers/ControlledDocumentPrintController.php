<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\DMS\Services\ControlledDocumentAccessService;
use App\Domain\DMS\Services\ControlledDocumentPdfService;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\PrintLayoutRegistry;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Domain\Shared\Services\AuditLogService;
use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\Organization;
use App\Models\ReportTemplate;
use App\Models\SopAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ControlledDocumentPrintController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ControlledDocumentPdfService $pdfService,
        private readonly ControlledDocumentAccessService $accessService,
    ) {}

    public function __invoke(Request $request, ControlledDocument $controlledDocument): StreamedResponse
    {
        $accessMode = (string) $request->route('access_mode', 'print');
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

        $isAuthorized = match ($accessMode) {
            'view' => $this->accessService->canView($request->user(), $controlledDocument),
            'download' => $this->accessService->canDownload($request->user(), $controlledDocument),
            default => $this->accessService->canPrint($request->user(), $controlledDocument),
        };

        if (! $isAuthorized) {
            $this->auditLogService->log(
                action: SopAuditLog::ACTION_PDF_ACCESS_DENIED,
                newValues: ['requested_action' => $accessMode],
                document: $controlledDocument,
            );

            throw new AccessDeniedHttpException('You do not have permission to '.$accessMode.' this controlled PDF.');
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
            'organization',
            'referencedSop',
            'sections',
            'template',
            'variables',
        ]);

        $reportTemplate = ReportTemplate::query()
            ->active()
            ->where('scope', ReportScope::ControlledDocument)
            ->where('format', ReportFormat::Pdf)
            ->when(
                $request->filled('template'),
                fn ($query) => $query->whereKey($request->integer('template')),
                fn ($query) => $query->where('is_system', true)->oldest(),
            )
            ->first();

        if ($reportTemplate === null && $request->filled('template')) {
            abort(404);
        }

        $layoutRegistry = app(PrintLayoutRegistry::class);
        $reportTemplate ??= new ReportTemplate([
            'layout_key' => 'sop-gmp-standard',
            'name' => 'GMP SOP Header / Footer',
            'scope' => ReportScope::ControlledDocument,
            'format' => ReportFormat::Pdf,
            'fields' => app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument),
            'page_settings' => $layoutRegistry->defaultPageSettings(),
            'header_zones' => $layoutRegistry->defaultHeaderZones(),
            'footer_zones' => $layoutRegistry->defaultFooterZones(),
            'is_active' => true,
            'is_system' => true,
        ]);

        $artifact = $this->pdfService->getOrGenerate(
            document: $controlledDocument,
            reportTemplate: $reportTemplate,
            issuance: $issuance,
            organization: $this->organizationIdentity($controlledDocument),
            generatedBy: $request->user(),
        );

        $auditAction = match ($accessMode) {
            'view' => SopAuditLog::ACTION_VIEWED,
            'download' => SopAuditLog::ACTION_DOWNLOADED,
            default => SopAuditLog::ACTION_PRINTED,
        };

        $this->auditLogService->log(
            action: $auditAction,
            newValues: [
                'issuance_id' => $issuance?->id,
                'issuance_number' => $issuance?->issuance_number,
                'watermark_code' => $issuance?->watermark_code,
                'report_template_id' => $reportTemplate->id,
                'report_template_key' => $reportTemplate->layout_key,
                'pdf_artifact_id' => $artifact->id,
                'pdf_sha256' => $artifact->sha256,
            ],
            document: $controlledDocument,
        );

        $disposition = $accessMode === 'download' ? 'attachment' : 'inline';

        return Storage::disk($artifact->disk)->response(
            $artifact->path,
            $artifact->filename,
            [
                'Content-Type' => $artifact->mime_type,
                'Content-Disposition' => $disposition.'; filename="'.$artifact->filename.'"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Document-SHA256' => $artifact->sha256,
            ],
        );
    }

    /** @return array<string, mixed> */
    private function organizationIdentity(ControlledDocument $document): array
    {
        if (filled($document->organization_snapshot)) {
            return $document->organization_snapshot;
        }

        return ($document->organization ?? Organization::defaultActive())?->identitySnapshot() ?? [];
    }
}
