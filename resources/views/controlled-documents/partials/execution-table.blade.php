@if ($section->section_type === \App\Models\ControlledDocumentSection::TYPE_REPEATING_LOG)
    <table>
        <thead>
            <tr>
                <th>Date / Time</th>
                <th>Response</th>
                <th>Result</th>
                <th>Comments</th>
                <th>Completed by</th>
                <th>Verified by</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($section->items as $item)
                <tr>
                    <td>{{ app(\App\Support\Formatting\DateFormatSettings::class)->formatDateTime($item->scheduled_at) ?? '-' }}</td>
                    <td>{{ $item->formattedResponse() ?? '________________' }} {{ $item->unit }}</td>
                    <td>{{ filled($item->result_status) ? str($item->result_status)->title() : '-' }}</td>
                    <td>{{ $item->comments ?? '-' }}</td>
                    <td>{{ $item->completedBy?->name ?? '-' }}</td>
                    <td>{{ $item->verifiedBy?->name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    @php($executionTables = $section->items->groupBy(fn ($item) => $item->source_table_id === null && blank($item->table_title) ? 'legacy' : 'table_'.$item->table_order))

    @foreach ($executionTables as $tableItems)
        @php($tableDefinition = $tableItems->first())
        @php($tableLayout = $tableDefinition?->table_layout ?: data_get($section->configuration, 'execution_layout', 'table'))
        @php($executionRows = $tableItems->groupBy('row_number'))

        <div class="execution-table-block">

        @if (filled($tableDefinition?->table_title))
            <div class="execution-table-title">{{ $tableDefinition->table_title }}</div>
        @endif

        @if ($tableLayout === 'field_value')
            <table>
                <tbody>
                    @foreach ($executionRows as $rowNumber => $executionRow)
                        @if ($executionRows->count() > 1)
                            <tr>
                                <th colspan="2">Entry {{ $rowNumber }}</th>
                            </tr>
                        @endif
                        @foreach ($executionRow as $item)
                            <tr>
                                <th>
                                    {{ $item->label }}
                                    @if (filled($item->unit))
                                        ({{ $item->unit }})
                                    @endif
                                </th>
                                <td>
                                    <div>{{ $item->formattedResponse() ?? '________________' }}</div>
                                    @if (filled($item->result_status))
                                        <div class="muted">Result: {{ str($item->result_status)->title() }}</div>
                                    @endif
                                    @if (filled($item->comments))
                                        <div class="muted">Comments: {{ $item->comments }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @else
            @php($executionHeaders = $executionRows->first())
            <table>
                <thead>
                    <tr>
                        @foreach ($executionHeaders as $item)
                            <th>
                                {{ $item->label }}
                                @if (filled($item->unit))
                                    ({{ $item->unit }})
                                @endif
                            </th>
                        @endforeach
                        <th>Completed by</th>
                        <th>Verified by</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($executionRows as $executionRow)
                        @php($completedBy = $executionRow->pluck('completedBy.name')->filter()->unique()->implode(', '))
                        @php($verifiedBy = $executionRow->pluck('verifiedBy.name')->filter()->unique()->implode(', '))
                        <tr>
                            @foreach ($executionHeaders as $header)
                                @php($item = $executionRow->firstWhere('source_item_id', $header->source_item_id))
                                <td>
                                    @if ($item !== null)
                                        <div>{{ $item->formattedResponse() ?? '________________' }}</div>
                                        @if (filled($item->result_status))
                                            <div class="muted">Result: {{ str($item->result_status)->title() }}</div>
                                        @endif
                                        @if (filled($item->comments))
                                            <div class="muted">Comments: {{ $item->comments }}</div>
                                        @endif
                                    @else
                                        <div>________________</div>
                                    @endif
                                </td>
                            @endforeach
                            <td>{{ $completedBy !== '' ? $completedBy : '-' }}</td>
                            <td>{{ $verifiedBy !== '' ? $verifiedBy : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        </div>
    @endforeach
@endif
