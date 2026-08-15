<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\ControlledDocumentSectionReviewService;
use App\Models\ControlledDocumentSection;
use App\Models\ControlledDocumentSectionReviewComment;
use App\Models\User;

class AddSectionReviewCommentAction
{
    public function __construct(private readonly ControlledDocumentSectionReviewService $sectionReviewService) {}

    public function execute(
        ControlledDocumentSection $section,
        User $reviewer,
        string $body,
    ): ControlledDocumentSectionReviewComment {
        return $this->sectionReviewService->addComment($section, $reviewer, $body);
    }
}
