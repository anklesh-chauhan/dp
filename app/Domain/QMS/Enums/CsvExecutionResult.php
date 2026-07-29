<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum CsvExecutionResult: string
{
    case NotRun = 'not_run';
    case Passed = 'passed';
    case Failed = 'failed';
    case Blocked = 'blocked';
}
