<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Domain\DMS\Services\DocumentTrainingService;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentTrainingAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

class AssignDocumentTrainingAction
{
    public function __construct(private readonly DocumentTrainingService $documentTrainingService) {}

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, ControlledDocumentTrainingAssignment>
     */
    public function execute(ControlledDocument $document, User $user, array $userIds): Collection
    {
        return $this->documentTrainingService->assign($document, $user, $userIds);
    }
}
