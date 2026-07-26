<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AttachmentIntegrityStatus: string implements HasColor, HasLabel
{
    case Verified = 'verified';
    case Missing = 'missing';
    case Tampered = 'tampered';
    case Unverified = 'unverified';

    public function getLabel(): string
    {
        return match ($this) {
            self::Verified => 'Verified',
            self::Missing => 'File Missing',
            self::Tampered => 'Integrity Failed',
            self::Unverified => 'Not Hashed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Verified => 'success',
            self::Missing,
            self::Tampered => 'danger',
            self::Unverified => 'warning',
        };
    }
}
