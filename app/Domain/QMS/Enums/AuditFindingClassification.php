<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum AuditFindingClassification: string
{
    case Nonconformity = 'nonconformity';
    case Observation = 'observation';
    case OpportunityForImprovement = 'opportunity_for_improvement';
    case PositivePractice = 'positive_practice';
}
