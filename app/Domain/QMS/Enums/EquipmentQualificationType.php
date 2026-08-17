<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

use Filament\Support\Contracts\HasLabel;

enum EquipmentQualificationType: string implements HasLabel
{
    case Dq = 'dq';
    case Iq = 'iq';
    case Oq = 'oq';
    case Pq = 'pq';
    case ProcessValidation = 'process_validation';
    case CleaningValidation = 'cleaning_validation';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dq => 'DQ',
            self::Iq => 'IQ',
            self::Oq => 'OQ',
            self::Pq => 'PQ',
            self::ProcessValidation => 'Process validation',
            self::CleaningValidation => 'Cleaning validation',
        };
    }
}
