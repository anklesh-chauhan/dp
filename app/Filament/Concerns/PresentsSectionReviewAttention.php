<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\ControlledDocument;

/**
 * @property-read ControlledDocument $record
 */
trait PresentsSectionReviewAttention
{
    protected function withSectionReviewAttention(?string $subheading): ?string
    {
        $attention = $this->record->sectionReviewAttentionSummary();

        if ($attention === null) {
            return $subheading;
        }

        return filled($subheading) ? "{$subheading} {$attention}" : $attention;
    }
}
