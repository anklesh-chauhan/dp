<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ControlledDocumentData;
use App\Domain\DMS\Actions\CreateDocumentFromTemplateAction;
use App\Models\DocumentImportItem;
use App\Models\TemplateStatus;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CreateControlledDocumentFromImportItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $itemId,
        public int $templateId,
        public int $defaultOwnerId,
    ) {}

    public function handle(): void
    {
        $item = DocumentImportItem::query()->findOrFail($this->itemId);
        if ($item->controlled_document_id !== null || $item->status !== 'completed') {
            return;
        }

        $metadata = $item->metadata ?? [];
        $document = app(CreateDocumentFromTemplateAction::class)->execute(new ControlledDocumentData(
            templateId: $this->templateId,
            title: (string) ($metadata['title'] ?? pathinfo($item->original_name, PATHINFO_FILENAME)),
            ownerId: (int) ($metadata['owner_id'] ?? $this->defaultOwnerId),
            createdBy: (int) $item->created_by,
            templateVersionId: $this->publishedTemplateVersionId(),
            documentNumber: (string) ($metadata['document_number'] ?? $this->fallbackDocumentNumber()),
            effectiveDate: $this->metadataDate($metadata['effective_date'] ?? null),
            reviewDate: $this->metadataDate($metadata['review_date'] ?? null),
        ));

        $item->update(['controlled_document_id' => $document->getKey()]);
        $item->originalArtifact?->linkToControlledDocument($document);
    }

    private function publishedTemplateVersionId(): ?int
    {
        return app('db')->table('document_template_versions')
            ->where('document_template_id', $this->templateId)
            ->where('template_status_id', TemplateStatus::query()->where('code', TemplateStatus::PUBLISHED)->value('id'))
            ->latest('version')
            ->value('id');
    }

    private function fallbackDocumentNumber(): string
    {
        return 'IMPORTED-'.$this->itemId;
    }

    private function metadataDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'm/d/Y'] as $format) {
            try {
                return CarbonImmutable::createFromFormat($format, trim($value));
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }
}
