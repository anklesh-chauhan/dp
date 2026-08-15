<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\ControlledDocumentSectionReviewService;
use App\Models\ControlledDocumentSectionReviewComment;
use App\Models\User;

class ResolveSectionReviewCommentAction
{
    public function __construct(private readonly ControlledDocumentSectionReviewService $sectionReviewService) {}

    public function execute(
        ControlledDocumentSectionReviewComment $comment,
        User $user,
    ): ControlledDocumentSectionReviewComment {
        return $this->sectionReviewService->resolveComment($comment, $user);
    }
}
