<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\TemplateDraftRevisionService;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\User;

final class CreateTemplateDraftRevisionAction
{
    public function __construct(private readonly TemplateDraftRevisionService $revisionService) {}

    public function execute(DocumentTemplate $template, User $user, string $changeReason): DocumentTemplateVersion
    {
        return $this->revisionService->create($template, $user, $changeReason);
    }
}
