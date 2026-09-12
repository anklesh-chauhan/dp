<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\DMS\Contracts\ControlledDocumentPdfRenderer;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\PrintLayoutRegistry;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Domain\Shared\Services\AuditLogService;
use App\Jobs\GenerateIssuancePrintPackJob;
use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\DocumentIssuanceBatch;
use App\Models\Organization;
use App\Models\ReportTemplate;
use App\Models\SopAuditLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class IssuancePrintPackService
{
    public function __construct(
        private readonly ControlledDocumentPdfRenderer $renderer,
        private readonly AuditLogService $auditLogService,
        private readonly DocumentIssuanceAccessService $issuanceAccessService,
        private readonly ControlledDocumentAccessService $documentAccessService,
    ) {}

    /**
     * @return array{queued: bool, batch: DocumentIssuanceBatch}
     */
    public function request(DocumentIssuanceBatch $batch, User $user): array
    {
        $batch->loadMissing(['document', 'issuances']);
        $copies = $batch->printableCopies();

        if ($copies->isEmpty() || $copies->contains(fn (DocumentIssuance $issuance): bool => ! $issuance->isPaper())) {
            throw ValidationException::withMessages([
                'pack' => 'Print packs are only available for paper copies.',
            ]);
        }

        if ($batch->isPackReady()) {
            return ['queued' => false, 'batch' => $batch];
        }

        if ($batch->copy_count > DocumentIssuanceBatch::SYNC_PACK_THRESHOLD) {
            $batch->update([
                'pack_status' => DocumentIssuanceBatch::PACK_PENDING,
                'pack_error' => null,
            ]);

            GenerateIssuancePrintPackJob::dispatch($batch->id, $user->id);

            return ['queued' => true, 'batch' => $batch->fresh()];
        }

        return ['queued' => false, 'batch' => $this->generate($batch, $user)];
    }

    /**
     * @param  Collection<int, DocumentIssuance>  $issuances
     * @return array{queued: bool, batch: DocumentIssuanceBatch}
     */
    public function requestForIssuances(Collection $issuances, User $user): array
    {
        $copies = $this->assertPrintableSelection($issuances, $user);
        $reuse = $this->reusablePaperBatch($copies);

        if ($reuse instanceof DocumentIssuanceBatch) {
            return $this->request($reuse, $user);
        }

        $first = $copies->first();
        $last = $copies->last();

        assert($first instanceof DocumentIssuance && $last instanceof DocumentIssuance);

        $batch = DocumentIssuanceBatch::query()->create([
            'document_id' => $first->document_id,
            'issued_by' => $user->id,
            'copy_count' => $copies->count(),
            'first_issuance_number' => $first->issuance_number,
            'last_issuance_number' => $last->issuance_number,
            'issuance_ids' => $copies->pluck('id')->all(),
            'pack_status' => DocumentIssuanceBatch::PACK_PENDING,
        ]);

        if ($copies->count() > DocumentIssuanceBatch::SYNC_PACK_THRESHOLD) {
            GenerateIssuancePrintPackJob::dispatch($batch->id, $user->id);

            return ['queued' => true, 'batch' => $batch->fresh()];
        }

        return ['queued' => false, 'batch' => $this->generate($batch, $user)];
    }

    /**
     * @return Collection<int, string>
     */
    public function parseIssuanceNumbers(string $value): Collection
    {
        $numbers = collect();

        foreach (preg_split('/[,;\n\r]+/', $value) ?: [] as $token) {
            $token = $this->normalizeIssuanceToken((string) $token);

            if ($token === '') {
                continue;
            }

            $range = $this->expandedIssuanceRange($token);

            if ($range instanceof Collection) {
                $numbers = $numbers->concat($range);

                continue;
            }

            $numbers->push($token);
        }

        return $numbers->unique()->values();
    }

    public function generate(DocumentIssuanceBatch $batch, User $user): DocumentIssuanceBatch
    {
        $batch->loadMissing([
            'document.template',
            'document.organization',
            'document.documentStatus',
            'document.department',
            'document.sections.executionTables.items',
            'document.sections.items',
            'issuances',
        ]);

        $document = $batch->document;
        $issuances = $batch->printableCopies();

        if ($issuances->isEmpty()) {
            throw ValidationException::withMessages([
                'pack' => 'This print pack has no copies to print.',
            ]);
        }

        $contents = $this->renderer->renderPack(
            $document,
            $this->reportTemplate($document),
            $issuances,
            $this->organizationIdentity($document),
            $user,
        );

        if (! str_starts_with($contents, '%PDF-')) {
            throw new RuntimeException('The document renderer did not return a valid PDF.');
        }

        $sha256 = hash('sha256', $contents);
        $filename = sprintf(
            '%s-v%s-%s-%s-%s.pdf',
            $document->document_number,
            $document->version,
            filled($batch->issuance_ids) ? 'copies' : 'paper',
            $batch->first_issuance_number ?? 'C01',
            $batch->last_issuance_number ?? 'C01',
        );
        $path = sprintf(
            'issuance-print-packs/%s/%s.pdf',
            $batch->id,
            $sha256,
        );

        if (! Storage::disk('local')->put($path, $contents)) {
            throw new RuntimeException('The print pack could not be stored.');
        }

        $batch->update([
            'pack_status' => DocumentIssuanceBatch::PACK_READY,
            'pack_disk' => 'local',
            'pack_path' => $path,
            'pack_filename' => $filename,
            'pack_sha256' => $sha256,
            'pack_size_bytes' => strlen($contents),
            'pack_error' => null,
            'generated_by' => $user->id,
            'generated_at' => now(),
        ]);

        $this->auditLogService->log(
            action: SopAuditLog::ACTION_PRINTED,
            newValues: [
                'issuance_batch_id' => $batch->id,
                'copy_count' => $batch->copy_count,
                'first_issuance_number' => $batch->first_issuance_number,
                'last_issuance_number' => $batch->last_issuance_number,
                'pdf_sha256' => $sha256,
            ],
            userId: $user->id,
            document: $document,
        );

        return $batch->fresh();
    }

    public function markFailed(DocumentIssuanceBatch $batch, string $message): void
    {
        $batch->update([
            'pack_status' => DocumentIssuanceBatch::PACK_FAILED,
            'pack_error' => $message,
        ]);
    }

    public function assertIntegrity(DocumentIssuanceBatch $batch): void
    {
        if (! $batch->isPackReady()) {
            throw new RuntimeException('The print pack is not ready.');
        }

        $contents = Storage::disk($batch->pack_disk)->get($batch->pack_path);

        if (! hash_equals((string) $batch->pack_sha256, hash('sha256', $contents))) {
            throw new RuntimeException('Stored print-pack integrity verification failed.');
        }
    }

    private function reportTemplate(ControlledDocument $document): ReportTemplate
    {
        $reportTemplate = ReportTemplate::query()
            ->active()
            ->where('scope', ReportScope::ControlledDocument)
            ->where('format', ReportFormat::Pdf)
            ->when(
                $document->template?->report_template_id,
                fn ($query) => $query->whereKey($document->template->report_template_id),
            )
            ->when(
                ! $document->template?->report_template_id,
                fn ($query) => $query->where('is_system', true)->oldest(),
            )
            ->first();

        if ($reportTemplate instanceof ReportTemplate) {
            return $reportTemplate;
        }

        $layoutRegistry = app(PrintLayoutRegistry::class);

        return new ReportTemplate([
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
    }

    /** @return array<string, mixed> */
    private function organizationIdentity(ControlledDocument $document): array
    {
        if (filled($document->organization_snapshot)) {
            return $document->organization_snapshot;
        }

        return ($document->organization ?? Organization::defaultActive())?->identitySnapshot() ?? [];
    }

    private function normalizeIssuanceToken(string $token): string
    {
        $token = strtoupper(trim($token));
        $token = preg_replace('/\s*[–—]\s*/u', '-', $token) ?? $token;
        $token = preg_replace('/\s+TO\s+/', '-', $token) ?? $token;

        return preg_replace('/\s+/', '', $token) ?? $token;
    }

    /**
     * @return Collection<int, string>|null
     */
    private function expandedIssuanceRange(string $token): ?Collection
    {
        if (! preg_match('/^(?P<start>.+?-C(?P<from>\d+))-(?P<end>.+-C(?P<to>\d+))$/i', $token, $matches)) {
            return null;
        }

        $startDocument = $this->documentNumberFromIssuanceNumber(strtoupper($matches['start']));
        $endDocument = $this->documentNumberFromIssuanceNumber(strtoupper($matches['end']));

        if ($startDocument === null || $endDocument === null) {
            return null;
        }

        if ($startDocument !== $endDocument) {
            throw ValidationException::withMessages([
                'issuance_numbers' => 'A copy range must use the same document number on both sides. Example: SOP-QA-00001-C01-SOP-QA-00001-C100.',
            ]);
        }

        $from = (int) $matches['from'];
        $to = (int) $matches['to'];

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        if (($to - $from + 1) > DocumentIssuanceBatch::MAX_PAPER_COPIES) {
            throw ValidationException::withMessages([
                'issuance_numbers' => 'A copy range cannot include more than '.DocumentIssuanceBatch::MAX_PAPER_COPIES.' copies.',
            ]);
        }

        return collect(range($from, $to))
            ->map(fn (int $copyNumber): string => sprintf('%s-C%02d', $startDocument, $copyNumber));
    }

    private function documentNumberFromIssuanceNumber(string $issuanceNumber): ?string
    {
        if (! preg_match('/^(.*)-C\d+$/', $issuanceNumber, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @param  Collection<int, DocumentIssuance>  $issuances
     * @return Collection<int, DocumentIssuance>
     */
    private function assertPrintableSelection(Collection $issuances, User $user): Collection
    {
        $ids = $issuances
            ->map(fn (DocumentIssuance $issuance): int => $issuance->id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'issuances' => 'Select at least one active controlled copy to print.',
            ]);
        }

        if ($ids->count() > DocumentIssuanceBatch::MAX_PAPER_COPIES) {
            throw ValidationException::withMessages([
                'issuances' => 'Print at most '.DocumentIssuanceBatch::MAX_PAPER_COPIES.' controlled copies in one pack.',
            ]);
        }

        $copies = DocumentIssuance::query()
            ->with(['document', 'issuanceStatus'])
            ->whereIn('id', $ids->all())
            ->orderBy('copy_number')
            ->get();

        if ($copies->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'issuances' => 'One or more selected copies could not be found.',
            ]);
        }

        if ($copies->pluck('document_id')->unique()->count() !== 1) {
            throw ValidationException::withMessages([
                'issuances' => 'Print copies from one master document at a time.',
            ]);
        }

        if ($copies->contains(fn (DocumentIssuance $issuance): bool => ! $issuance->isActive())) {
            throw ValidationException::withMessages([
                'issuances' => 'Only active controlled copies can be printed.',
            ]);
        }

        $denied = $copies->first(
            fn (DocumentIssuance $issuance): bool => ! $this->issuanceAccessService->canAccess($user, $issuance),
        );

        if ($denied instanceof DocumentIssuance) {
            throw ValidationException::withMessages([
                'issuances' => 'You do not have access to one or more of the selected copies.',
            ]);
        }

        $document = $copies->first()?->document;

        if (! $document instanceof ControlledDocument || ! $this->documentAccessService->canPrint($user, $document)) {
            throw ValidationException::withMessages([
                'issuances' => 'You do not have permission to print this controlled document.',
            ]);
        }

        return $copies;
    }

    /**
     * @param  Collection<int, DocumentIssuance>  $copies
     */
    private function reusablePaperBatch(Collection $copies): ?DocumentIssuanceBatch
    {
        $batchIds = $copies->pluck('issuance_batch_id')->unique()->filter();

        if ($batchIds->count() !== 1 || $copies->contains(fn (DocumentIssuance $issuance): bool => ! $issuance->isPaper())) {
            return null;
        }

        $batch = DocumentIssuanceBatch::query()->find($batchIds->first());

        if (! $batch instanceof DocumentIssuanceBatch) {
            return null;
        }

        $batchCopyIds = $batch->issuances()->pluck('id')->sort()->values();
        $selectedIds = $copies->pluck('id')->sort()->values();

        if ($batchCopyIds->all() !== $selectedIds->all()) {
            return null;
        }

        return $batch;
    }
}
