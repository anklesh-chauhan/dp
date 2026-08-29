<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Enums;

enum DataClassification: string
{
    case Public = 'public';

    case Internal = 'internal';

    case Confidential = 'confidential';

    case Restricted = 'restricted';
}
