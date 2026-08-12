<x-filament-panels::page>
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
                    </div>

                    <div class="mt-5 grid gap-2">
                        <x-filament::input.wrapper :valid="! $errors->has('userMessage')">
                            <textarea
                                class="fi-input block min-h-28 w-full resize-y border-none bg-transparent px-3 py-2 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 sm:text-sm dark:text-white dark:placeholder:text-gray-500"
                                wire:model="userMessage"
                                placeholder="Tell the assistant what to create or what to revise..."
                            ></textarea>
                        </x-filament::input.wrapper>
                        @error('userMessage')
                            <p class="text-sm text-danger-600">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-wrap items-center gap-3">
                            <x-filament::button wire:click="sendMessage" wire:loading.attr="disabled">
                                Send
                            </x-filament::button>
                            <x-filament::button color="gray" wire:click="resetConversation">
                                Start over
                            </x-filament::button>
                            <span class="text-sm text-gray-500" wire:loading wire:target="sendMessage">
                                Preparing the next response...
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
                            color="success"
                            wire:click="createDraft"
                            wire:confirm="Create this Draft controlled document from the exact preview shown?"
                            wire:loading.attr="disabled"
                        >
                            Confirm and create Draft
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
