<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\RetentionLifecycleService;
use App\Models\DocumentTemplate;
use App\Models\User;

class DestroyTemplateAction
{
    public function __construct(private readonly RetentionLifecycleService $retentionLifecycleService) {}

    public function execute(DocumentTemplate $template, User $user, string $reason): DocumentTemplate
    {
        return $this->retentionLifecycleService->destroyTemplate($template, $user, $reason);
    }
}
