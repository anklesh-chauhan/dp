<x-filament-panels::page>
    <div class="space-y-8">
        <section>
            <h2 class="text-lg font-semibold mb-4">Schedule M gap assessment</h2>
            @if ($this->readiness['gap_assessment'] === null)
                <p class="text-sm text-gray-500 dark:text-gray-400">No in-progress or approved gap assessment found.</p>
            @else
                @php($gap = $this->readiness['gap_assessment'])
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Assessment</div>
                        <div class="mt-1 text-lg font-semibold">{{ $gap['assessment_number'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $gap['status'] ?? '') }}</div>
                    </div>
                    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Percent compliant</div>
                        <div class="mt-1 text-3xl font-semibold tabular-nums">{{ $gap['percent_compliant'] }}%</div>
                    </div>
                    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Compliant items</div>
                        <div class="mt-1 text-3xl font-semibold tabular-nums">{{ $gap['compliant_items'] }}</div>
                    </div>
                    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total items</div>
                        <div class="mt-1 text-3xl font-semibold tabular-nums">{{ $gap['total_items'] }}</div>
                    </div>
                </div>
            @endif
        </section>

        <section>
            <h2 class="text-lg font-semibold mb-4">Readiness indicators</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Overdue calibrations</div>
                    <div class="mt-1 text-3xl font-semibold tabular-nums">
                        {{ $this->readiness['overdue_calibrations'] === null ? '—' : $this->readiness['overdue_calibrations'] }}
                    </div>
                    @if ($this->readiness['overdue_calibrations'] === null)
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Calibration module not available</div>
                    @endif
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Open critical audit findings</div>
                    <div class="mt-1 text-3xl font-semibold tabular-nums">{{ $this->readiness['open_critical_audit_findings'] }}</div>
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Open deviations</div>
                    <div class="mt-1 text-3xl font-semibold tabular-nums">{{ $this->readiness['open_deviations'] }}</div>
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Overdue deviations</div>
                    <div class="mt-1 text-3xl font-semibold tabular-nums">{{ $this->readiness['overdue_deviations'] }}</div>
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Open CAPAs</div>
                    <div class="mt-1 text-3xl font-semibold tabular-nums">{{ $this->readiness['open_capas'] }}</div>
                </div>
                <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Overdue CAPAs</div>
                    <div class="mt-1 text-3xl font-semibold tabular-nums">{{ $this->readiness['overdue_capas'] }}</div>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
