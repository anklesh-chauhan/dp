<?php

declare(strict_types=1);

namespace App\Enums;

enum SopRole: string
{
    case Administrator = 'sop administrator';
    case Maker = 'sop maker';
    case Checker = 'sop checker';
    case Approver = 'sop approver';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'SOP Administrator',
            self::Maker => 'SOP Maker',
            self::Checker => 'SOP Checker',
            self::Approver => 'SOP Approver',
        };
    }
}
