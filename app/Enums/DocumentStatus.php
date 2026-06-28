<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Effective = 'effective';
    case Obsolete = 'obsolete';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Effective => 'Effective',
            self::Obsolete => 'Obsolete',
            self::Rejected => 'Rejected',
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
