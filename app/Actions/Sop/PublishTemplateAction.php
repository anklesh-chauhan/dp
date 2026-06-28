<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Services\Sop\TemplatePublisherService;

class PublishTemplateAction
{
    public function __construct(private readonly TemplatePublisherService $publisherService) {}

    public function execute(SopTemplate $template, int $userId, ?string $changeReason = null): SopTemplateVersion
    {
        return $this->publisherService->publish($template, $userId, $changeReason);
    }
}
