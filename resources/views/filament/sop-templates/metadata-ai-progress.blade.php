<div
    @if ($this->metadataAiTaskPolling)
        wire:poll.3s="refreshMetadataAiTask"
    @endif
    class="space-y-3"
>
    <div class="text-sm font-medium">
        {{ $this->metadataAiCurrentStep ?? 'Processing AI metadata' }}
    </div>

    <div class="text-xs text-gray-500 dark:text-gray-400">
        Progress: {{ $this->metadataAiProgress }}%
    </div>

    <progress
        max="100"
        value="{{ $this->metadataAiProgress }}"
        class="w-full"
    ></progress>
</div>
