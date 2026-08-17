<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

use Filament\Support\Contracts\HasLabel;

enum EquipmentAssetCategory: string implements HasLabel
{
    case Production = 'production';
    case Packaging = 'packaging';
    case Laboratory = 'laboratory';
    case Utility = 'utility';
    case Facility = 'facility';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Production => 'Production',
            self::Packaging => 'Packaging',
            self::Laboratory => 'Laboratory',
            self::Utility => 'Utility',
            self::Facility => 'Facility',
            self::Other => 'Other',
        };
    }
}
