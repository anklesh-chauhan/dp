<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Enums;

enum ReportFormat: string
{
    case Pdf = 'pdf';
    case Csv = 'csv';
    case Excel = 'xlsx';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF / Print',
            self::Csv => 'CSV',
            self::Excel => 'Excel',
        };
    }
}
