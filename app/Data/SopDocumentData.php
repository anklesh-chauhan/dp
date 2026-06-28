<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonInterface;

final readonly class SopDocumentData
{
    /**
     * @param  array<string, mixed>  $variables
     */
    public function __construct(
        public int $templateId,
        public string $title,
        public int $ownerId,
        public int $createdBy,
        public array $variables = [],
        public ?CarbonInterface $effectiveDate = null,
        public ?CarbonInterface $reviewDate = null,
        public ?int $templateVersionId = null,
        public ?string $documentNumber = null,
    ) {}
}
