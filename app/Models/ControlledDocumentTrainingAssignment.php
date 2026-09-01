<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\TMS\Models\TrainingAssignment as TmsTrainingAssignment;

/**
 * @deprecated Use {@see TmsTrainingAssignment} instead.
 */
class ControlledDocumentTrainingAssignment extends TmsTrainingAssignment
{
    protected $table = 'training_assignments';
}
