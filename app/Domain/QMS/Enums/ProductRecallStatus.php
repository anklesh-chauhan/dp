<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum ProductRecallStatus: string
{
    case Draft = 'draft';
    case Initiated = 'initiated';
    case RiskClassified = 'risk_classified';
    case NotificationInProgress = 'notification_in_progress';
    case ExecutionInProgress = 'execution_in_progress';
    case EffectivenessCheck = 'effectiveness_check';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
