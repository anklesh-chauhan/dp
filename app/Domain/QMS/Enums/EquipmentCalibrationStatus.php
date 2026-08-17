<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EquipmentCalibrationStatus: string implements HasColor, HasLabel
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case OutOfTolerance = 'out_of_tolerance';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::OutOfTolerance => 'Out of Tolerance',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Scheduled => 'gray',
            self::InProgress => 'info',
            self::Completed => 'success',
            self::OutOfTolerance => 'danger',
            self::Cancelled => 'warning',
        };
    }
}
