@php
    $blank = '________________';
    $logRows = max(8, min(30, (int) data_get($section->configuration, 'row_count', 12)));
@endphp

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
            @for ($row = 1; $row <= $logRows; $row++)
                <tr>
                    <td>{{ $blank }}</td>
                    <td>{{ $blank }}</td>
                    <td>{{ $blank }}</td>
                    <td>{{ $blank }}</td>
                    <td>{{ $blank }}</td>
                    <td>{{ $blank }}</td>
                </tr>
            @endfor
        </tbody>
    </table>
@elseif ($section->relationLoaded('executionTables') && $section->executionTables->isNotEmpty())
    @foreach ($section->executionTables as $table)
        @php($columns = $table->items)
        @php($rowCount = max(1, min(100, (int) ($table->row_count ?: 1))) )
        @if ($columns->isNotEmpty())
            <div class="execution-table-block">
                @if (filled($table->title))
                    <div class="execution-table-title">{{ $table->title }}</div>
                @endif
                <table>
                    <thead>
                        <tr>
                            @foreach ($columns as $column)
                                <th>
                                    {{ $column->label }}
                                    @if (filled($column->unit))
                                        ({{ $column->unit }})
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for ($row = 1; $row <= $rowCount; $row++)
                            <tr>
                                @foreach ($columns as $column)
                                    <td>{{ $blank }}</td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach
@elseif ($section->items->isNotEmpty())
    <table>
        <tbody>
            @foreach ($section->items as $item)
                <tr>
                    <th>
                        {{ $item->label }}
                        @if (filled($item->unit))
                            ({{ $item->unit }})
                        @endif
                    </th>
                    <td>{{ $blank }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
