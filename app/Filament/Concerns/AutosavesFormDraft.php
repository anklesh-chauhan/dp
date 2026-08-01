<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\FormDraft;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

trait AutosavesFormDraft
{
    public bool $draftRestored = false;

    public function mountAutosavesFormDraft(): void
    {
        if (! $this->draftAutosaveEnabled() || ! auth()->check()) {
            return;
        }

        $draft = FormDraft::query()
            ->where('user_id', auth()->id())
            ->where('form_key', $this->draftFormKey())
            ->first();

        if ($draft === null || $draft->payload === []) {
            return;
        }

        $this->form->fill($draft->payload);
        $this->draftRestored = true;

        Notification::make()
            ->title('Draft restored')
            ->body('Your previous unsaved work has been restored.')
            ->success()
            ->send();
    }

    public function updatedData(): void
    {
        $this->saveFormDraft();
    }

    public function saveFormDraft(): void
    {
        if (! $this->draftAutosaveEnabled() || ! auth()->check()) {
            return;
        }

        $draft = FormDraft::query()
            ->where('user_id', auth()->id())
            ->where('form_key', $this->draftFormKey())
            ->first();

        if ($draft !== null && $draft->last_saved_at?->diffInSeconds(now()) < $this->draftAutosaveIntervalSeconds()) {
            return;
        }

        FormDraft::query()->updateOrCreate(
            ['user_id' => auth()->id(), 'form_key' => $this->draftFormKey()],
            ['payload' => $this->data, 'last_saved_at' => Carbon::now()],
        );
    }

    protected function clearFormDraft(): void
    {
        FormDraft::query()
            ->where('user_id', auth()->id())
            ->where('form_key', $this->draftFormKey())
            ->delete();
    }

    protected function draftAutosaveEnabled(): bool
    {
        return (bool) config('qms.draft_autosave.enabled', true);
    }

    protected function draftAutosaveIntervalSeconds(): int
    {
        return max(1, (int) config('qms.draft_autosave.interval_seconds', 5));
    }

    abstract protected function draftFormKey(): string;
}
