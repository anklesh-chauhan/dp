<footer
    class="print-grid {{ $footerZones['show_borders'] ? 'print-grid-bordered' : '' }}"
    style="gap: {{ $footerZones['gap_mm'] }}mm; grid-template-columns: {{ collect($footerZones['columns'])->pluck('width')->map(fn ($width) => $width.'fr')->implode(' ') }};"
>
    @foreach ($footerZones['columns'] as $column)
        @include('reports.partials.print-zone', ['items' => $column['items'], 'alignment' => $column['alignment'], 'verticalAlignment' => $column['vertical_alignment'], 'document' => $document ?? null, 'organization' => $organization ?? [], 'issuance' => $issuance ?? null, 'reportTemplate' => $reportTemplate, 'preview' => $preview ?? false, 'serverPdf' => $serverPdf ?? false])
    @endforeach
</footer>
