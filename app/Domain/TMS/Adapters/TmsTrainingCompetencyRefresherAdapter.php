<?php

declare(strict_types=1);

namespace App\Domain\TMS\Adapters;

use App\Domain\Shared\Contracts\TrainingCompetencyRefresher;
use App\Domain\TMS\Services\CompetencyService;
use App\Models\User;

final class TmsTrainingCompetencyRefresherAdapter implements TrainingCompetencyRefresher
{
    public function __construct(
        private readonly CompetencyService $competencyService,
    ) {}

    public function refreshForUserDocument(User $user, int $controlledDocumentId): void
    {
        $this->competencyService->refreshForUserDocument($user, $controlledDocumentId);
    }
}
