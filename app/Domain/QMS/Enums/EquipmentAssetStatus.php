<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum EquipmentAssetStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Decommissioned = 'decommissioned';
}
