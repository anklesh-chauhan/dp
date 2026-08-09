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
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
            <table class="min-w-full border-collapse text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-950 dark:bg-white/5 dark:text-white">
                    <tr>
                        <th class="w-20 border-b border-r border-gray-200 px-3 py-3 dark:border-white/10">
                            {{ $isScheduled() ? 'Date / Time' : 'Row' }}
                        </th>
                        <template x-for="column in columns" :key="column.key">
                            <th class="min-w-40 border-b border-r border-gray-200 px-3 py-3 dark:border-white/10">
                                <span x-text="column.label + (column.unit ? ` (${column.unit})` : '')"></span>
                                <span x-show="column.required" class="text-danger-600">*</span>
                            </th>
                        </template>
                        <th class="min-w-52 border-b border-r border-gray-200 px-3 py-3 dark:border-white/10">Comments</th>
                        <th class="min-w-52 border-b border-gray-200 px-3 py-3 dark:border-white/10">Verified by</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-transparent">
                    <template x-for="(row, rowIndex) in state" :key="rowIndex">
                        <tr>
                            <td class="border-r border-gray-200 px-3 py-2 font-medium text-gray-700 dark:border-white/10 dark:text-gray-200" x-text="row.row_label"></td>
                            <template x-for="column in columns" :key="column.key">
                                <td class="border-r border-gray-200 p-2 dark:border-white/10">
                                    <input
                                        type="text"
                                        x-model="row.responses[column.key]"
                                        x-bind:placeholder="column.placeholder"
                                        x-bind:required="column.required"
                                        maxlength="100"
                                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/20 dark:bg-white/5 dark:text-white"
                                    />
                                </td>
                            </template>
                            <td class="border-r border-gray-200 p-2 dark:border-white/10">
                                <textarea
                                    x-model="row.comments"
                                    rows="1"
                                    placeholder="Optional notes"
                                    class="block min-h-10 w-full resize-y rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/20 dark:bg-white/5 dark:text-white"
                                ></textarea>
                            </td>
                            <td class="p-2">
                                <select
                                    x-model="row.verified_by"
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/20 dark:bg-gray-900 dark:text-white"
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

        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Enter one record per row. Required columns are marked with an asterisk.
        </p>
    </div>
</x-dynamic-component>
