<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\TrainingCompetencyRefresher;
use App\Models\User;

final class NullTrainingCompetencyRefresher implements TrainingCompetencyRefresher
{
    public function refreshForUserDocument(User $user, int $controlledDocumentId): void {}
}
