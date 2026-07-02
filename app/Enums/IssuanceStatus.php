<?php

declare(strict_types=1);

namespace App\Enums;

enum IssuanceStatus: string
{
    case Active = 'active';
    case Recalled = 'recalled';
    case Destroyed = 'destroyed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Recalled => 'Recalled',
            self::Destroyed => 'Destroyed',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
