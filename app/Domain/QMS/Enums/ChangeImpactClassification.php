<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ChangeImpactClassification: string implements HasColor, HasLabel
{
    case Minor = 'minor';
    case Major = 'major';
    case Critical = 'critical';

    public function getLabel(): string
    {
        return match ($this) {
            self::Minor => 'Minor',
            self::Major => 'Major',
            self::Critical => 'Critical',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Minor => 'gray',
            self::Major => 'warning',
            self::Critical => 'danger',
        };
    }

    public function requiresAcceptedRiskAssessment(): bool
    {
        return $this === self::Major || $this === self::Critical;
    }
}
