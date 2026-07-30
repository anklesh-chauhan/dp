<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Enums;

enum ReportScope: string
{
    case ControlledDocument = 'controlled_document';
    case ChangeControl = 'change_control';
    case DocumentDistribution = 'document_distribution';

    public function label(): string
    {
        return match ($this) {
            self::ControlledDocument => 'Controlled Document',
            self::ChangeControl => 'Change Control Investigation',
            self::DocumentDistribution => 'Document Distribution Sheet',
        };
    }
}
