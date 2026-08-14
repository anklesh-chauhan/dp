<x-filament-panels::page>
    @if ($this->reports()->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">No reports are available for your role.</p>
    @else
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->reports() as $report)
                <a
                    href="{{ $report['url'] }}"
                    class="block rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition hover:ring-primary-500 dark:bg-gray-900 dark:ring-white/10"
                >
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $report['title'] }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $report['description'] }}</p>
                </a>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
