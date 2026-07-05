<?php

declare(strict_types=1);

namespace App\Actions\Sop;

use App\Models\SopTemplate;
use App\Models\User;
use App\Services\Sop\RetentionLifecycleService;

class MarkTemplateObsoleteAction
{
    public function __construct(private readonly RetentionLifecycleService $retentionLifecycleService) {}

    public function execute(SopTemplate $template, User $user, ?string $reason = null): SopTemplate
    {
        return $this->retentionLifecycleService->markTemplateObsolete($template, $user, $reason);
    }
}
