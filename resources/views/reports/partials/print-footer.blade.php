<footer class="print-table {{ $footerZones['show_borders'] ? 'print-table-bordered' : '' }}" style="gap: {{ $footerZones['gap_mm'] }}mm;">
    @foreach ($footerZones['rows'] as $row)
        <div class="print-table-row" style="gap: {{ $footerZones['gap_mm'] }}mm; grid-template-columns: {{ collect($row['cells'])->pluck('width')->map(fn ($width) => $width.'fr')->implode(' ') }};">
            @foreach ($row['cells'] as $cell)
                @include('reports.partials.print-zone', ['items' => $cell['items'], 'alignment' => $cell['alignment'], 'verticalAlignment' => $cell['vertical_alignment'], 'document' => $document ?? null, 'organization' => $organization ?? [], 'issuance' => $issuance ?? null, 'reportTemplate' => $reportTemplate, 'preview' => $preview ?? false, 'serverPdf' => $serverPdf ?? false])
            @endforeach
        </div>
    @endforeach
</footer>
