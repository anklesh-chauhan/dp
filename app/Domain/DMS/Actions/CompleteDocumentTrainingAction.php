<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\DocumentTrainingService;
use App\Domain\TMS\Models\TrainingAssignment;
use App\Models\User;

class CompleteDocumentTrainingAction
{
    public function __construct(private readonly DocumentTrainingService $documentTrainingService) {}

    public function execute(
        TrainingAssignment $assignment,
        User $user,
        ?string $comments = null,
    ): TrainingAssignment {
        return $this->documentTrainingService->complete($assignment, $user, $comments);
    }
}
