<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\RetentionLifecycleService;
use App\Models\SopTemplate;
use App\Models\User;

class MarkTemplateObsoleteAction
{
    public function __construct(private readonly RetentionLifecycleService $retentionLifecycleService) {}

    public function execute(SopTemplate $template, User $user, ?string $reason = null): SopTemplate
    {
        return $this->retentionLifecycleService->markTemplateObsolete($template, $user, $reason);
    }
}
