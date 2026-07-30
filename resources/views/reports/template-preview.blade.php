<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Preview - {{ $reportTemplate->name }}</title>
    <style>
        :root { --primary: {{ $pageSettings['primary_color'] }}; --secondary: {{ $pageSettings['secondary_color'] }}; }
        body { background: #e2e8f0; color: var(--primary); font: {{ $pageSettings['font_size'] }}pt/1.45 {{ match($pageSettings['font_family']) { 'times' => '"Times New Roman", serif', 'georgia' => 'Georgia, serif', default => 'Arial, sans-serif' } }}; margin: 0; padding: 24px; }
        .notice { background: #fef3c7; border: 1px solid #f59e0b; margin: 0 auto 16px; max-width: 960px; padding: 10px; text-align: center; }
        .page { background: white; box-shadow: 0 12px 36px #0f172a26; margin: auto; max-width: {{ $pageSettings['orientation'] === 'landscape' ? '1120px' : '794px' }}; min-height: 900px; padding: {{ $pageSettings['margin_top_mm'] }}mm {{ $pageSettings['margin_right_mm'] }}mm {{ $pageSettings['margin_bottom_mm'] }}mm {{ $pageSettings['margin_left_mm'] }}mm; }
        .print-grid { display: grid; }
        .print-grid-bordered { border: 2px solid var(--primary); }
        .print-zone { display: flex; flex-direction: column; gap: 4px; min-height: 58px; padding: 8px; }
        .print-grid-bordered .print-zone + .print-zone { border-left: 1px solid var(--primary); }
        .print-zone-center { align-items: center; text-align: center; } .print-zone-right { align-items: flex-end; text-align: right; }
        .print-zone-vertical-top { justify-content: flex-start; } .print-zone-vertical-center { justify-content: center; } .print-zone-vertical-bottom { justify-content: flex-end; }
        .sample-logo { align-items: center; border: 1px dashed #64748b; display: flex; font-size: 10px; height: 34px; justify-content: center; width: 82px; }
        .zone-document-title { font-size: 16px; }
        .body-grid { display: grid; gap: 12px; grid-template-columns: repeat(2, 1fr); margin: 20px 0; }
        .block { border: {{ $pageSettings['show_table_borders'] ? '1px solid #94a3b8' : '0' }}; padding: 12px; }
        .block-full { grid-column: 1 / -1; } .block h2 { background: var(--secondary); font-size: 13px; margin: -12px -12px 10px; padding: 7px 10px; }
        .sample-lines { background: repeating-linear-gradient(to bottom, transparent 0, transparent 20px, #cbd5e1 21px); height: 65px; }
        .preview-footer { margin-top: 20px; }
        @page { size: {{ $pageSettings['paper_size'] }} {{ $pageSettings['orientation'] }}; margin: {{ $pageSettings['margin_top_mm'] }}mm {{ $pageSettings['margin_right_mm'] }}mm {{ $pageSettings['margin_bottom_mm'] }}mm {{ $pageSettings['margin_left_mm'] }}mm; }
    </style>
</head>
<body>
    <div class="notice">SAMPLE PREVIEW · NOT A CONTROLLED DOCUMENT</div>
    <main class="page">
        <header
            class="print-grid {{ $headerZones['show_borders'] ? 'print-grid-bordered' : '' }}"
            style="gap: {{ $headerZones['gap_mm'] }}mm; grid-template-columns: {{ collect($headerZones['columns'])->pluck('width')->map(fn ($width) => $width.'fr')->implode(' ') }};"
        >
            @foreach ($headerZones['columns'] as $column)
                @include('reports.partials.print-zone', ['items' => $column['items'], 'alignment' => $column['alignment'], 'verticalAlignment' => $column['vertical_alignment'], 'preview' => true, 'reportTemplate' => $reportTemplate])
            @endforeach
        </header>
        <section class="body-grid">
            @foreach ($reportTemplate->fields as $field)
                @if ($field['enabled'])
                    <article class="block {{ ($field['width'] ?? 'full') === 'full' ? 'block-full' : '' }}" style="{{ ($field['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
                        <h2>{{ $field['label'] }}</h2>
                        <div class="sample-lines"></div>
                    </article>
                @endif
            @endforeach
        </section>
        <footer
            class="print-grid preview-footer {{ $footerZones['show_borders'] ? 'print-grid-bordered' : '' }}"
            style="gap: {{ $footerZones['gap_mm'] }}mm; grid-template-columns: {{ collect($footerZones['columns'])->pluck('width')->map(fn ($width) => $width.'fr')->implode(' ') }};"
        >
            @foreach ($footerZones['columns'] as $column)
                @include('reports.partials.print-zone', ['items' => $column['items'], 'alignment' => $column['alignment'], 'verticalAlignment' => $column['vertical_alignment'], 'preview' => true, 'reportTemplate' => $reportTemplate])
            @endforeach
        </footer>
    </main>
</body>
</html>
