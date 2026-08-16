<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\DocumentTrainingService;
use App\Models\ControlledDocumentTrainingAssignment;
use App\Models\User;

class RemoveDocumentTrainingAssignmentAction
{
    public function __construct(private readonly DocumentTrainingService $documentTrainingService) {}

    public function execute(ControlledDocumentTrainingAssignment $assignment, User $user): void
    {
        $this->documentTrainingService->remove($assignment, $user);
    }
}
