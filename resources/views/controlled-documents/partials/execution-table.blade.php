@if ($section->section_type === \App\Models\ControlledDocumentSection::TYPE_REPEATING_LOG)
    <table>
        <thead>
            <tr>
                <th>Date / Time</th>
                <th>Response</th>
                <th>Result</th>
                <th>Comments</th>
                <th>Completed / Verified</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($section->items as $item)
                <tr>
                    <td>{{ $item->scheduled_at?->toDayDateTimeString() ?? '-' }}</td>
                    <td>{{ $item->response ?? '________________' }} {{ $item->unit }}</td>
                    <td>{{ filled($item->result_status) ? str($item->result_status)->title() : '-' }}</td>
                    <td>{{ $item->comments ?? '-' }}</td>
                    <td>{{ $item->completedBy?->name ?? '-' }} / {{ $item->verifiedBy?->name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@elseif (data_get($section->configuration, 'execution_layout', 'table') === 'field_value')
    @php($executionRows = $section->items->groupBy('row_number'))
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
                            <div>{{ $item->response ?? '________________' }}</div>
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
    @php($executionRows = $section->items->groupBy('row_number'))
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
            </tr>
        </thead>
        <tbody>
            @foreach ($executionRows as $executionRow)
                <tr>
                    @foreach ($executionRow as $item)
                        <td>
                            <div>{{ $item->response ?? '________________' }}</div>
                            @if (filled($item->result_status))
                                <div class="muted">Result: {{ str($item->result_status)->title() }}</div>
                            @endif
                            @if (filled($item->comments))
                                <div class="muted">Comments: {{ $item->comments }}</div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
