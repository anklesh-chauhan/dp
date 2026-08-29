<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-4">
        <aside class="grid content-start gap-4 xl:col-span-1">
            <x-filament::section>
                <x-slot name="heading">Chat history</x-slot>

                <div class="grid gap-2">
                    <x-filament::button color="gray" type="button" wire:click="startNewConversation" wire:loading.attr="disabled">
                        New conversation
                    </x-filament::button>

                    @forelse ($this->conversationHistory as $conversation)
                        <button
                            type="button"
                            wire:click="openConversation('{{ $conversation['id'] }}')"
                            @class([
                                'grid gap-1 rounded-xl border px-3 py-2 text-start transition',
                                'border-primary-500 bg-primary-50 dark:bg-primary-500/10' => $conversationId === $conversation['id'],
                                'border-gray-200 hover:border-primary-300 dark:border-white/10 dark:hover:border-primary-500' => $conversationId !== $conversation['id'],
                            ])
                        >
                            <span class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $conversation['title'] }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $conversation['updated_at'] }}</span>
                        </button>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Your conversations will appear here.</p>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Read-only assistant</x-slot>
                <div class="grid gap-3 text-sm text-gray-600 dark:text-gray-300">
                    <p>Searches only records you are permitted to view and links every record-specific answer to its source.</p>
                    <p>It cannot create, edit, submit, approve, sign, publish, activate, close, or delete records.</p>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Try asking</x-slot>
                <ul class="grid gap-3 text-sm text-gray-600 dark:text-gray-300">
                    <li>“Find the effective change-control SOP.”</li>
                    <li>“What approvals are waiting for me?”</li>
                    <li>“Summarize deviation DEV-…”</li>
                    <li>“Show open change controls about validation.”</li>
                </ul>
            </x-filament::section>
        </aside>

        <div class="xl:col-span-3">
            <x-filament::section>
                <x-slot name="heading">Conversation</x-slot>
                <x-slot name="description">
                    Internal record queries run through the locally configured AI provider.
                </x-slot>

                <div class="grid max-h-[36rem] min-h-80 gap-4 overflow-y-auto pe-1">
                    @forelse ($this->messages as $chatMessage)
                        <article @class([
                            'max-w-[92%] rounded-2xl px-4 py-3 text-sm leading-6 shadow-sm',
                            'ms-auto bg-primary-600 text-white' => $chatMessage['role'] === 'user',
                            'me-auto bg-gray-100 text-gray-950 dark:bg-white/10 dark:text-white' => $chatMessage['role'] === 'assistant',
                        ])>
                            @if ($chatMessage['role'] === 'assistant')
                                <div class="prose prose-sm max-w-none dark:prose-invert [&_a]:font-medium [&_a]:text-primary-600 dark:[&_a]:text-primary-400">
                                    {!! $this->renderMessage($chatMessage['content']) !!}
                                </div>
                            @else
                                <div class="whitespace-pre-wrap">{{ $chatMessage['content'] }}</div>
                            @endif
                        </article>
                    @empty
                        <div class="grid place-items-center rounded-2xl border border-dashed border-gray-300 p-8 text-center dark:border-white/15">
                            <div class="grid max-w-lg gap-2">
                                <p class="font-medium text-gray-950 dark:text-white">Ask about authorized documents, quality records, or your approval queue.</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Answers based on application records include links back to the source.</p>
                            </div>
                        </div>
                    @endforelse

                    @if ($pendingMessage !== '')
                        <article class="ms-auto max-w-[92%] rounded-2xl bg-primary-600 px-4 py-3 text-sm leading-6 text-white shadow-sm">
                            <div class="whitespace-pre-wrap">{{ $pendingMessage }}</div>
                        </article>

                        <article class="me-auto max-w-[92%] rounded-2xl bg-gray-100 px-4 py-3 text-sm leading-6 text-gray-950 shadow-sm dark:bg-white/10 dark:text-white">
                            <div class="whitespace-pre-wrap" wire:stream="assistant-response"></div>
                        </article>
                    @endif
                </div>

                <form class="mt-6 grid gap-3" wire:submit="sendMessage">
                    <x-filament::input.wrapper :valid="! $errors->has('userMessage')">
                        <textarea
                            class="fi-input block min-h-28 w-full resize-y border-none bg-transparent px-3 py-2 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 sm:text-sm dark:text-white dark:placeholder:text-gray-500"
                            wire:model="userMessage"
                            placeholder="Ask a question about documents, QMS records, or pending approvals..."
                        ></textarea>
                    </x-filament::input.wrapper>
                    @error('userMessage')
                        <p class="text-sm text-danger-600">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-wrap items-center gap-3">
                        <x-filament::button type="submit" wire:loading.attr="disabled">
                            Send
                        </x-filament::button>
                        <span class="text-sm text-gray-500 dark:text-gray-400" wire:loading wire:target="streamResponse">
                            Checking authorized sources and streaming the response…
                        </span>
                    </div>
                </form>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
