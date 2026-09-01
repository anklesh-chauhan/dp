<?php

declare(strict_types=1);

namespace App\Domain\TMS\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserCompetencyStatus: string implements HasColor, HasLabel
{
    case Assigned = 'assigned';
    case Trained = 'trained';
    case Overdue = 'overdue';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::Trained => 'Trained',
            self::Overdue => 'Overdue',
            self::Expired => 'Expired',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Assigned => 'gray',
            self::Trained => 'success',
            self::Overdue => 'warning',
            self::Expired => 'danger',
        };
    }

    public function isCurrent(): bool
    {
        return $this === self::Trained;
    }
}
