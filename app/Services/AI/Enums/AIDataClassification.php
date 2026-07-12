<?php

declare(strict_types=1);

namespace App\Services\AI\Enums;

enum AIDataClassification: string
{
    case PUBLIC = 'public';

    case INTERNAL = 'internal';

    case CONFIDENTIAL = 'confidential';

    case RESTRICTED = 'restricted';
}
