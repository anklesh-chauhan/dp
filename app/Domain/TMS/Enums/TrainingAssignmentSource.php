<?php

declare(strict_types=1);

namespace App\Domain\TMS\Enums;

enum TrainingAssignmentSource: string
{
    case ControlledDocument = 'controlled_document';

    case TrainingProgram = 'training_program';
}
