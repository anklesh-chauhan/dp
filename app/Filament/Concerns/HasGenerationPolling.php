<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

trait HasGenerationPolling
{
    public function refreshGenerationStatus(): void
    {
        $wasInProgress = $this->record->isGenerationInProgress();
        $this->record->refresh();

        if ($wasInProgress && ! $this->record->isGenerationInProgress()) {
            $this->redirect(static::getUrl(['record' => $this->record]));
        }
    }
}
