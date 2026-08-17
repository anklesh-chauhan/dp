<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EquipmentCalibrationResult: string implements HasColor, HasLabel
{
    case Pass = 'pass';
    case Fail = 'fail';
    case OutOfTolerance = 'out_of_tolerance';
    case Pending = 'pending';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pass => 'Pass',
            self::Fail => 'Fail',
            self::OutOfTolerance => 'Out of Tolerance',
            self::Pending => 'Pending',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pass => 'success',
            self::Fail => 'danger',
            self::OutOfTolerance => 'danger',
            self::Pending => 'gray',
        };
    }
}
