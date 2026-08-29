<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Enums;

enum ValidationSeverity: string
{
    case Error = 'error';

    case Warning = 'warning';

    case Info = 'info';
}
