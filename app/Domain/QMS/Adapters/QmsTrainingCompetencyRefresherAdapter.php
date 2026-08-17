<?php

declare(strict_types=1);

namespace App\Domain\QMS\Adapters;

use App\Domain\QMS\Services\CompetencyService;
use App\Domain\Shared\Contracts\TrainingCompetencyRefresher;
use App\Models\User;

final class QmsTrainingCompetencyRefresherAdapter implements TrainingCompetencyRefresher
{
    public function __construct(
        private readonly CompetencyService $competencyService,
    ) {}

    public function refreshForUserDocument(User $user, int $controlledDocumentId): void
    {
        $this->competencyService->refreshForUserDocument($user, $controlledDocumentId);
    }
}
