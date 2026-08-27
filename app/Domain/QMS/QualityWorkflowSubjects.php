<?php

declare(strict_types=1);

namespace App\Domain\QMS;

use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\Deviation;

final class QualityWorkflowSubjects
{
    /**
     * @return array<class-string, string>
     */
    public static function options(): array
    {
        return [
            Deviation::class => 'Deviation',
            ChangeControl::class => 'Change Control',
        ];
    }

    public static function label(?string $subjectType): string
    {
        if (! is_string($subjectType) || $subjectType === '') {
            return '—';
        }

        return self::options()[$subjectType] ?? class_basename($subjectType);
    }
}
