<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Draft chat history</x-slot>
        <x-slot name="description">Reopen a previous drafting session, including its current background-job status.</x-slot>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @forelse ($this->draftHistory as $historyItem)
                <button
                    type="button"
                    wire:click="openDraftSession({{ $historyItem['id'] }})"
                    @class([
                        'grid gap-1 rounded-xl border px-4 py-3 text-start transition',
                        'border-primary-500 bg-primary-50 dark:bg-primary-500/10' => $draftSessionId === $historyItem['id'],
                        'border-gray-200 hover:border-primary-300 dark:border-white/10 dark:hover:border-primary-500' => $draftSessionId !== $historyItem['id'],
                    ])
                >
                    <span class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $historyItem['title'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $historyItem['context'] }} · {{ $historyItem['status'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $historyItem['updated_at'] }}</span>
                </button>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Your drafting conversations will appear here.</p>
            @endforelse
        </div>
    </x-filament::section>

    @if (! $this->session)
        <x-filament::section>
            <x-slot name="heading">Start a controlled-document draft</x-slot>
            <x-slot name="description">
                Select an approved template. The assistant will collect your requirements and prepare a preview without creating a document.
            </x-slot>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="grid gap-2">
                    <label class="text-sm font-medium text-gray-950 dark:text-white" for="templateId">Published template</label>
                    <x-filament::input.wrapper :valid="! $errors->has('templateId')">
                        <x-filament::input.select id="templateId" wire:model.live="templateId">
                            <option value="">Select a template</option>
                            @foreach ($this->templateOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    @error('templateId')
                        <p class="text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-2">
                    <label class="text-sm font-medium text-gray-950 dark:text-white" for="ownerId">Document owner</label>
                    <x-filament::input.wrapper :valid="! $errors->has('ownerId')">
                        <x-filament::input.select id="ownerId" wire:model="ownerId">
                            <option value="">Select an owner</option>
                            @foreach ($this->ownerOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    @error('ownerId')
                        <p class="text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ($this->requiresSopReference)
                    <div class="grid gap-2 lg:col-span-2">
                        <label class="text-sm font-medium text-gray-950 dark:text-white" for="referencedControlledDocumentId">Referenced effective SOP</label>
                        <x-filament::input.wrapper :valid="! $errors->has('referencedControlledDocumentId')">
                            <x-filament::input.select id="referencedControlledDocumentId" wire:model="referencedControlledDocumentId">
                                <option value="">Select an effective SOP</option>
                                @foreach ($this->referenceOptions as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        @error('referencedControlledDocumentId')
                            <p class="text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            <div class="mt-6">
                <x-filament::button wire:click="startConversation" wire:loading.attr="disabled">
                    Start conversation
                </x-filament::button>
            </div>
        </x-filament::section>
    @else
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
            <div class="grid content-start gap-6 xl:col-span-2">
                <x-filament::section>
                    <x-slot name="heading">Document conversation</x-slot>
                    <x-slot name="description">
                        {{ $this->session->template->name }} · {{ $this->session->template->documentType->name }}
                    </x-slot>

                    <div class="grid max-h-[32rem] gap-3 overflow-y-auto pe-1">
                        @forelse ($this->messages as $chatMessage)
                            <div @class([
                                'max-w-[90%] rounded-xl px-4 py-3 text-sm whitespace-pre-wrap',
                                'ms-auto bg-primary-600 text-white' => $chatMessage['role'] === 'user',
                                'me-auto bg-gray-100 text-gray-950 dark:bg-white/10 dark:text-white' => $chatMessage['role'] !== 'user',
                            ])>
                                {{ $chatMessage['content'] }}
                            </div>
                        @empty
                            <div class="rounded-xl bg-gray-50 p-4 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300">
                                Describe the document you need in your own words. Include its purpose, users, scope, process, responsibilities, and any important controls you already know.
                            </div>
                        @endforelse

                        @if ($this->draftRequestActive)
                            <div class="ms-auto max-w-[90%] rounded-xl bg-primary-600 px-4 py-3 text-sm whitespace-pre-wrap text-white">
                                {{ $this->draftRequest->message }}
                            </div>
                        @endif
                    </div>

                    @if ($this->draftRequest)
                        <div
                            @if ($this->draftRequestActive) wire:poll.2s="refreshDraftStatus" @endif
                            @class([
                                'mt-5 rounded-xl border px-4 py-3 text-sm',
                                'border-warning-300 bg-warning-50 text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200' => $this->draftRequest->status->value === 'queued',
                                'border-primary-300 bg-primary-50 text-primary-800 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-200' => $this->draftRequest->status->value === 'processing',
                                'border-success-300 bg-success-50 text-success-800 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-200' => $this->draftRequest->status->value === 'completed',
                                'border-danger-300 bg-danger-50 text-danger-800 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-200' => $this->draftRequest->status->value === 'failed',
                            ])
                        >
                            <div class="flex items-center gap-3">
                                @if ($this->draftRequestActive)
                                    <x-filament::loading-indicator class="h-5 w-5 shrink-0" />
                                @endif
                                <div class="grid gap-1">
                                    <p class="font-medium">
                                        @switch($this->draftRequest->status->value)
                                            @case('queued')
                                                Request queued
                                                @break
                                            @case('processing')
                                                AI is preparing the draft response
                                                @break
                                            @case('completed')
                                                Draft response ready
                                                @break
                                            @default
                                                Drafting request failed
                                        @endswitch
                                    </p>
                                    <p>
                                        @switch($this->draftRequest->status->value)
                                            @case('queued')
                                                Waiting for an available background worker. This page will update automatically.
                                                @break
                                            @case('processing')
                                                The request is running in the background, so the page will not time out.
                                                @break
                                            @case('completed')
                                                The conversation and preview have been updated.
                                                @break
                                            @default
                                                {{ $this->draftRequest->failure_message }}
                                        @endswitch
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-5 grid gap-2">
                        <x-filament::input.wrapper :valid="! $errors->has('userMessage')">
                            <textarea
                                class="fi-input block min-h-28 w-full resize-y border-none bg-transparent px-3 py-2 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 sm:text-sm dark:text-white dark:placeholder:text-gray-500"
                                wire:model="userMessage"
                                @disabled($this->draftRequestActive)
                                placeholder="Tell the assistant what to create or what to revise..."
                            ></textarea>
                        </x-filament::input.wrapper>
                        @error('userMessage')
                            <p class="text-sm text-danger-600">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-wrap items-center gap-3">
                            <x-filament::button
                                wire:click="sendMessage"
                                wire:loading.attr="disabled"
                                :disabled="$this->draftRequestActive"
                            >
                                Send
                            </x-filament::button>
                            <x-filament::button
                                color="gray"
                                wire:click="resetConversation"
                                :disabled="$this->draftRequestActive"
                            >
                                Start over
                            </x-filament::button>
                            <span class="text-sm text-gray-500" wire:loading wire:target="sendMessage">
                                Adding the request to the queue...
                            </span>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <div class="grid content-start gap-6 xl:col-span-3">
                <x-filament::section>
                    <x-slot name="heading">Structured brief</x-slot>
                    <x-slot name="description">
                        Preview revision {{ $this->session->preview_revision }}
                    </x-slot>

                    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Title</dt>
                            <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $this->session->title ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Owner</dt>
                            <dd class="mt-1 text-sm text-gray-950 dark:text-white">{{ $this->session->owner->name }}</dd>
                        </div>
                        @foreach (($this->session->brief ?? []) as $label => $value)
                            <div class="md:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ str($label)->replace('_', ' ')->title() }}</dt>
                                <dd class="mt-1 whitespace-pre-wrap text-sm text-gray-950 dark:text-white">{{ filled($value) ? $value : 'Not provided' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">Document preview</x-slot>
                    <x-slot name="description">
                        This preview is not yet a controlled document.
                    </x-slot>

                    <div class="grid gap-6">
                        @forelse ($this->previewSections as $section)
                            <article class="rounded-xl border border-gray-200 p-5 dark:border-white/10">
                                <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $section['title'] }}</h3>
                                <div class="mt-3 whitespace-pre-wrap text-sm leading-6 text-gray-700 dark:text-gray-300">{{ $section['content'] }}</div>
                            </article>
                        @empty
                            <p class="text-sm text-gray-500">Send your requirements to generate a preview.</p>
                        @endforelse
                    </div>
                </x-filament::section>

                @if ($this->session->status->value === 'preview_ready' && filled($expectedPreviewHash))
                    <x-filament::section>
                        <x-slot name="heading">Create the Draft</x-slot>
                        <x-slot name="description">
                            Confirming creates one Draft controlled document. It does not submit, approve, publish, or activate it.
                        </x-slot>

                        <x-filament::button
                            type="button"
                            color="success"
                            wire:click="createDraft"
                            wire:confirm="Create this Draft controlled document from the exact preview shown?"
                            wire:loading.attr="disabled"
                            wire:target="createDraft"
                        >
                            <span wire:loading.remove wire:target="createDraft">Confirm and create Draft</span>
                            <span wire:loading wire:target="createDraft">Creating Draft...</span>
                        </x-filament::button>
                        @error('confirmation')
                            <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </x-filament::section>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
