<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            columns: @js($getExecutionColumns()),
        }"
        {{ $getExtraAttributeBag() }}
    >
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0 text-sm">
                <thead class="text-left text-gray-700 dark:text-gray-200">
                    <tr>
                        <th @class([
                            'border-b border-r border-gray-200 bg-gray-50 px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide dark:border-white/10 dark:bg-gray-800',
                            'w-44 min-w-44' => $isScheduled(),
                            'w-12 min-w-12' => ! $isScheduled(),
                        ])>
                            @if ($isScheduled())
                                Date / Time
                            @else
                                <span class="sr-only">Row number</span>
                            @endif
                        </th>
                        <template x-for="column in columns" :key="column.key">
                            <th class="min-w-52 border-b border-r border-gray-200 bg-gray-50 px-3 py-3 align-top dark:border-white/10 dark:bg-gray-800">
                                <div class="font-semibold text-gray-950 dark:text-white">
                                    <span x-text="column.label"></span>
                                    <span x-show="column.required" class="text-danger-600">*</span>
                                </div>
                                <div class="mt-1 flex flex-wrap gap-1 text-xs font-normal text-gray-500 dark:text-gray-400">
                                    <span
                                        x-show="column.unit"
                                        x-text="column.unit"
                                        class="rounded-md bg-gray-100 px-1.5 py-0.5 dark:bg-white/10"
                                    ></span>
                                    <span
                                        x-show="column.value_type === 'numeric' && column.decimal_precision !== null"
                                        x-text="`${column.decimal_precision} decimal${column.decimal_precision === 1 ? '' : 's'}`"
                                        class="rounded-md bg-primary-50 px-1.5 py-0.5 text-primary-700 dark:bg-primary-400/10 dark:text-primary-300"
                                    ></span>
                                    <span
                                        x-show="column.value_type === 'boolean'"
                                        class="rounded-md bg-success-50 px-1.5 py-0.5 text-success-700 dark:bg-success-400/10 dark:text-success-300"
                                    >Pass / Fail</span>
                                </div>
                            </th>
                        </template>
                        <th class="min-w-64 border-b border-r border-gray-200 bg-gray-50 px-3 py-3 align-top font-semibold text-gray-950 dark:border-white/10 dark:bg-gray-800 dark:text-white">Comments</th>
                        <th class="min-w-56 border-b border-gray-200 bg-gray-50 px-3 py-3 align-top font-semibold text-gray-950 dark:border-white/10 dark:bg-gray-800 dark:text-white">Verified by</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, rowIndex) in state" :key="rowIndex">
                        <tr class="transition-colors odd:bg-white even:bg-gray-50/50 hover:bg-primary-50/40 dark:odd:bg-gray-900 dark:even:bg-white/5 dark:hover:bg-primary-400/5">
                            <td class="border-b border-r border-gray-200 bg-inherit px-3 py-3 text-center font-semibold text-gray-700 dark:border-white/10 dark:text-gray-200" x-text="row.row_label"></td>
                            <template x-for="column in columns" :key="column.key">
                                <td class="border-b border-r border-gray-200 p-2.5 align-top dark:border-white/10">
                                    <div class="flex items-center gap-2">
                                        <template x-if="column.value_type === 'numeric'">
                                            <input
                                                type="number"
                                                inputmode="decimal"
                                                x-model="row.responses[column.key]"
                                                x-bind:placeholder="column.placeholder"
                                                x-bind:required="column.required"
                                                x-bind:step="column.step"
                                                class="block h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/20 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500"
                                            />
                                        </template>

                                        <template x-if="column.value_type === 'boolean'">
                                            <select
                                                x-model="row.responses[column.key]"
                                                x-bind:required="column.required"
                                                class="block h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/20 dark:bg-gray-900 dark:text-white"
                                            >
                                                <option value="">Select result</option>
                                                <option value="Pass">Pass</option>
                                                <option value="Fail">Fail</option>
                                            </select>
                                        </template>

                                        <template x-if="column.value_type === 'text'">
                                            <input
                                                type="text"
                                                x-model="row.responses[column.key]"
                                                x-bind:placeholder="column.placeholder"
                                                x-bind:required="column.required"
                                                maxlength="100"
                                                class="block h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/20 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500"
                                            />
                                        </template>

                                        <span
                                            x-show="column.unit"
                                            x-text="column.unit"
                                            class="whitespace-nowrap text-xs font-medium text-gray-500 dark:text-gray-400"
                                        ></span>
                                    </div>
                                </td>
                            </template>
                            <td class="border-b border-r border-gray-200 p-2.5 align-top dark:border-white/10">
                                <textarea
                                    x-model="row.comments"
                                    rows="1"
                                    placeholder="Optional notes"
                                    class="block min-h-10 w-full resize-y rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/20 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500"
                                ></textarea>
                            </td>
                            <td class="border-b border-gray-200 p-2.5 align-top dark:border-white/10">
                                <select
                                    x-model="row.verified_by"
                                    class="block h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/20 dark:bg-gray-900 dark:text-white"
                                >
                                    <option value="">Select verifier</option>
                                    @foreach ($getVerifiers() as $verifierId => $verifierName)
                                        <option value="{{ $verifierId }}">{{ $verifierName }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>

            <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                Enter one record per row. Required columns are marked with an asterisk.
            </div>
        </div>
    </div>
</x-dynamic-component>
