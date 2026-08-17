<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Models\User;

interface TrainingCompetencyRefresher
{
    public function refreshForUserDocument(User $user, int $controlledDocumentId): void;
}
