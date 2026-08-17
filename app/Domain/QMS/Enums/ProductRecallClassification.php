<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductRecallClassification: string implements HasColor, HasLabel
{
    case ClassI = 'class_i';
    case ClassIi = 'class_ii';
    case ClassIii = 'class_iii';
    case NotClassified = 'not_classified';

    public function getLabel(): string
    {
        return match ($this) {
            self::ClassI => 'Class I',
            self::ClassIi => 'Class II',
            self::ClassIii => 'Class III',
            self::NotClassified => 'Not classified',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ClassI => 'danger',
            self::ClassIi => 'warning',
            self::ClassIii => 'info',
            self::NotClassified => 'gray',
        };
    }
}
