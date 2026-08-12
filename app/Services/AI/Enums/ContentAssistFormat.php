<?php

declare(strict_types=1);

namespace App\Services\AI\Enums;

enum ContentAssistFormat: string
{
    case Plain = 'plain';

    case Html = 'html';
}
