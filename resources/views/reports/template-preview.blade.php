<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview - {{ $reportTemplate->name }}</title>
    @php($portraitWidth = $pageSettings['paper_size'] === 'letter' ? 215.9 : 210)
    @php($portraitHeight = $pageSettings['paper_size'] === 'letter' ? 279.4 : 297)
    @php($pageWidth = $pageSettings['orientation'] === 'landscape' ? $portraitHeight : $portraitWidth)
    @php($pageHeight = $pageSettings['orientation'] === 'landscape' ? $portraitWidth : $portraitHeight)
    <style>
        * { box-sizing: border-box; }

        :root {
            --primary: {{ $pageSettings['primary_color'] }};
            --secondary: {{ $pageSettings['secondary_color'] }};
        }

        body {
            background: #e2e8f0;
            color: var(--primary);
            font-family: {{ match($pageSettings['font_family']) { 'times' => '"Times New Roman", serif', 'georgia' => 'Georgia, serif', default => 'Arial, Helvetica, sans-serif' } }};
            font-size: {{ $pageSettings['font_size'] }}pt;
            line-height: 1.2;
            margin: 0;
            padding: 24px;
        }

        .notice {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            margin: 0 auto 16px;
            max-width: {{ $pageWidth }}mm;
            padding: 10px;
            text-align: center;
        }

        .page {
            background: #fff;
            box-shadow: 0 12px 36px #0f172a26;
            display: flex;
            flex-direction: column;
            height: {{ $pageHeight }}mm;
            margin: auto;
            overflow: hidden;
            padding-bottom: 5mm;
            width: {{ $pageWidth }}mm;
        }

        .preview-header {
            margin: {{ $pageSettings['margin_top_mm'] }}mm {{ $pageSettings['margin_right_mm'] }}mm 0 {{ $pageSettings['margin_left_mm'] }}mm;
        }

        .preview-body {
            display: grid;
            flex: 1 1 auto;
            gap: 3mm;
            grid-auto-rows: min-content;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin: {{ $headerZones['repeat_every_page'] ? $headerZones['content_gap_mm'] : 5 }}mm {{ $pageSettings['margin_right_mm'] }}mm {{ $footerZones['repeat_every_page'] ? $footerZones['content_gap_mm'] : 5 }}mm {{ $pageSettings['margin_left_mm'] }}mm;
            min-height: 0;
            overflow: hidden;
        }

        .preview-footer {
            flex: 0 0 auto;
            margin: 0 {{ $pageSettings['margin_right_mm'] }}mm 0 {{ $pageSettings['margin_left_mm'] }}mm;
        }

        .print-grid { display: grid; width: 100%; }
        .print-grid-bordered { border: 1px solid var(--primary); }
        .print-grid-bordered .print-zone + .print-zone { border-left: 1px solid var(--primary); }
        .print-table { display: flex; flex-direction: column; width: 100%; }
        .print-table-row { display: grid; }
        .print-table-bordered { border-left: 1px solid var(--primary); border-top: 1px solid var(--primary); }
        .print-table-bordered .print-zone { border-bottom: 1px solid var(--primary); border-right: 1px solid var(--primary); }
        .print-zone { display: flex; flex-direction: column; justify-content: center; min-height: 7mm; overflow-wrap: anywhere; padding: 1mm; }
        .print-zone-center { align-items: center; text-align: center; }
        .print-zone-right { align-items: flex-end; text-align: right; }
        .print-zone-vertical-top { justify-content: flex-start; }
        .print-zone-vertical-center { justify-content: center; }
        .print-zone-vertical-bottom { justify-content: flex-end; }
        .print-zone-emphasized { font-weight: 700; }
        .sample-logo { align-items: center; border: 1px dashed #64748b; display: flex; font-size: .7em; height: 9mm; justify-content: center; width: 28mm; }
        .zone-document-title { font-size: 1em; }

        .block {
            border: {{ $pageSettings['show_table_borders'] ? '1px solid #d5dbe3' : '0' }};
            break-inside: avoid;
            min-height: 24mm;
            padding: 3mm;
        }

        .block-full { grid-column: 1 / -1; }

        .block h2 {
            background: var(--secondary);
            font-size: 1em;
            margin: -3mm -3mm 2mm;
            padding: 2mm 3mm;
        }

        .sample-lines {
            background: repeating-linear-gradient(to bottom, transparent 0, transparent 5mm, #d5dbe3 calc(5mm + 1px));
            height: 11mm;
        }

        @media (max-width: 900px) {
            body { padding: 12px; }
            .page-shell { overflow-x: auto; }
        }

        @page {
            size: {{ $pageSettings['paper_size'] }} {{ $pageSettings['orientation'] }};
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="notice">SAMPLE PREVIEW - NOT A CONTROLLED DOCUMENT</div>
    <div class="page-shell">
        <main class="page">
            <div class="preview-header">
                @include('reports.partials.print-header', ['preview' => true])
            </div>

            <section class="preview-body">
                @foreach ($reportTemplate->fields as $field)
                    @if ($field['enabled'])
                        <article class="block {{ ($field['width'] ?? 'full') === 'full' ? 'block-full' : '' }}">
                            <h2>{{ $field['label'] }}</h2>
                            <div class="sample-lines"></div>
                        </article>
                    @endif
                @endforeach
            </section>

            <div class="preview-footer">
                @include('reports.partials.print-footer', ['preview' => true])
            </div>
        </main>
    </div>
</body>
</html>
