<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum ApprovalDecisionCode: string
{
    case PENDING = 'pending';

    case APPROVED = 'approved';

    case REJECTED = 'rejected';

    case RETURNED = 'returned';
}
