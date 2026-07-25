<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Models\User;

interface ApprovalSubmissionAuthorization
{
    public function canSubmit(ApprovableSubject $subject, User $user): bool;
}
