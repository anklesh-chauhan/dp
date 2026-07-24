<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductModule: string
{
    case DMS = 'dms';

    case QMS = 'qms';

    case AI = 'ai';

    public function label(): string
    {
        return match ($this) {
            self::DMS => 'Document Management System',
            self::QMS => 'Quality Management System',
            self::AI => 'AI Assistant',
        };
    }

    /**
     * @return list<self>
     */
    public function dependencies(): array
    {
        return match ($this) {
            self::QMS, self::AI => [self::DMS],
            self::DMS => [],
        };
    }
}
