<footer
    class="print-grid {{ $footerZones['show_borders'] ? 'print-grid-bordered' : '' }}"
    style="gap: {{ $footerZones['gap_mm'] }}mm; grid-template-columns: {{ collect($footerZones['columns'])->pluck('width')->map(fn ($width) => $width.'fr')->implode(' ') }};"
>
    @foreach ($footerZones['columns'] as $column)
        @include('reports.partials.print-zone', ['items' => $column['items'], 'alignment' => $column['alignment'], 'verticalAlignment' => $column['vertical_alignment'], 'document' => $document, 'organization' => $organization, 'issuance' => $issuance, 'reportTemplate' => $reportTemplate, 'serverPdf' => $serverPdf ?? false])
    @endforeach
</footer>
