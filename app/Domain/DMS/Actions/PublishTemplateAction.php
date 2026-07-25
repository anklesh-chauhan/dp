<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\TemplatePublisherService;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;

class PublishTemplateAction
{
    public function __construct(private readonly TemplatePublisherService $publisherService) {}

    public function execute(SopTemplate $template, int $userId, ?string $changeReason = null): SopTemplateVersion
    {
        return $this->publisherService->publish($template, $userId, $changeReason);
    }
}
