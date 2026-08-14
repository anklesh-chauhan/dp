<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\DocumentStatus;

final class DocumentStatusColor
{
    public static function for(?string $code): string
    {
        return match ($code) {
            DocumentStatus::DRAFT => 'gray',
            DocumentStatus::UNDER_REVIEW => 'warning',
            DocumentStatus::APPROVED => 'info',
            DocumentStatus::EFFECTIVE => 'success',
            DocumentStatus::SUPERSEDED, DocumentStatus::OBSOLETE => 'warning',
            DocumentStatus::ARCHIVED, DocumentStatus::RETENTION_COMPLETED => 'gray',
            DocumentStatus::DESTROYED, DocumentStatus::REJECTED => 'danger',
            default => 'gray',
        };
    }
}
