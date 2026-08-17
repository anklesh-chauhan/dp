<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductReturnDisposition: string implements HasColor, HasLabel
{
    case Quarantine = 'quarantine';
    case Rework = 'rework';
    case Destroy = 'destroy';
    case ReleaseToStock = 'release_to_stock';
    case Pending = 'pending';

    public function getLabel(): string
    {
        return match ($this) {
            self::Quarantine => 'Quarantine',
            self::Rework => 'Rework',
            self::Destroy => 'Destroy',
            self::ReleaseToStock => 'Release to stock',
            self::Pending => 'Pending',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Quarantine => 'warning',
            self::Rework => 'info',
            self::Destroy => 'danger',
            self::ReleaseToStock => 'success',
            self::Pending => 'gray',
        };
    }
}
