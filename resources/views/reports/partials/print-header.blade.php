<header class="print-table {{ $headerZones['show_borders'] ? 'print-table-bordered' : '' }}" style="gap: {{ $headerZones['gap_mm'] }}mm;">
    @foreach ($headerZones['rows'] as $row)
        <div class="print-table-row" style="gap: {{ $headerZones['gap_mm'] }}mm; grid-template-columns: {{ collect($row['cells'])->pluck('width')->map(fn ($width) => $width.'fr')->implode(' ') }};">
            @foreach ($row['cells'] as $cell)
                @include('reports.partials.print-zone', ['items' => $cell['items'], 'alignment' => $cell['alignment'], 'verticalAlignment' => $cell['vertical_alignment'], 'document' => $document, 'organization' => $organization, 'issuance' => $issuance, 'reportTemplate' => $reportTemplate])
            @endforeach
        </div>
    @endforeach
</header>
