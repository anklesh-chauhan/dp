<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\TemplatePublisherService;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;

class PublishTemplateAction
{
    public function __construct(private readonly TemplatePublisherService $publisherService) {}

    public function execute(DocumentTemplate $template, int $userId, ?string $changeReason = null): DocumentTemplateVersion
    {
        return $this->publisherService->publish($template, $userId, $changeReason);
    }
}
