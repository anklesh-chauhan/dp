<x-filament-panels::page>
    <div class="space-y-8">
        <section>
            <h2 class="text-lg font-semibold mb-4">Overdue workload</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->snapshot['overdue'] as $domain => $count)
                    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', $domain) }}</div>
                        <div class="mt-1 text-3xl font-semibold tabular-nums">{{ $count }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="text-lg font-semibold mb-4">Lifecycle counts</h2>
            <div class="space-y-6">
                @foreach ($this->snapshot['lifecycles'] as $domain => $counts)
                    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                        <h3 class="font-medium mb-3 capitalize">{{ str_replace('_', ' ', $domain) }}</h3>
                        @if ($counts === [])
                            <p class="text-sm text-gray-500 dark:text-gray-400">No records</p>
                        @else
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ($counts as $status => $count)
                                    <div class="flex items-baseline justify-between gap-2 rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2">
                                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ str_replace('_', ' ', $status) }}</span>
                                        <span class="font-semibold tabular-nums">{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-panels::page>
