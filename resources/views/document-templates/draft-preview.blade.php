<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Draft Preview - {{ $template->name }}</title>
    @php($portraitWidth = $pageSettings['paper_size'] === 'letter' ? 215.9 : 210)
    @php($portraitHeight = $pageSettings['paper_size'] === 'letter' ? 279.4 : 297)
    @php($pageWidth = $pageSettings['orientation'] === 'landscape' ? $portraitHeight : $portraitWidth)
    @php($pageHeight = $pageSettings['orientation'] === 'landscape' ? $portraitWidth : $portraitHeight)
    <style>
        @page { size: A4; margin: 18mm; }
        body { background: #e5e7eb; color: {{ $pageSettings['primary_color'] }}; font-family: {{ match($pageSettings['font_family']) { 'times' => '"Times New Roman", serif', 'georgia' => 'Georgia, serif', default => 'Arial, Helvetica, sans-serif' } }}; font-size: {{ $pageSettings['font_size'] }}pt; margin: 0; }
        .toolbar { background: #111827; color: white; padding: 12px 20px; position: sticky; top: 0; text-align: right; }
        button { background: #2563eb; border: 0; border-radius: 6px; color: white; cursor: pointer; padding: 9px 14px; }
        .page { background: white; box-shadow: 0 2px 12px #0002; margin: 28px auto; min-height: {{ $pageHeight }}mm; padding: {{ $pageSettings['margin_top_mm'] }}mm {{ $pageSettings['margin_right_mm'] }}mm {{ $pageSettings['margin_bottom_mm'] }}mm {{ $pageSettings['margin_left_mm'] }}mm; width: {{ $pageWidth }}mm; }
        .notice { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; color: #92400e; margin-bottom: 24px; padding: 12px 16px; }
        h1 { border-bottom: 2px solid #1f2937; margin-bottom: 6px; padding-bottom: 10px; }
        h2 { border-bottom: 1px solid #d1d5db; margin-top: 34px; padding-bottom: 6px; }
        h3 { margin-bottom: 8px; }
        .meta, .muted { color: #6b7280; font-size: 13px; }
        .section { page-break-inside: avoid; }
        .content table { border-collapse: collapse; width: 100%; }
        .content td, .content th { border: 1px solid #d1d5db; padding: 6px; }
        .print-header, .print-footer { margin-bottom: 5mm; }
        .print-footer { margin-top: 5mm; }
        .print-grid { display: grid; width: 100%; }
        .print-grid-bordered { border: 1px solid {{ $pageSettings['primary_color'] }}; }
        .print-grid-bordered .print-zone + .print-zone { border-left: 1px solid {{ $pageSettings['primary_color'] }}; }
        .print-table { display: flex; flex-direction: column; width: 100%; }
        .print-table-row { display: grid; }
        .print-table-bordered { border-left: 1px solid {{ $pageSettings['primary_color'] }}; border-top: 1px solid {{ $pageSettings['primary_color'] }}; }
        .print-table-bordered .print-zone { border-bottom: 1px solid {{ $pageSettings['primary_color'] }}; border-right: 1px solid {{ $pageSettings['primary_color'] }}; }
        .print-zone { display: flex; flex-direction: column; justify-content: center; min-height: 7mm; overflow-wrap: anywhere; padding: 1mm; }
        .print-zone-center { align-items: center; text-align: center; }
        .print-zone-right { align-items: flex-end; text-align: right; }
        .print-zone-vertical-top { justify-content: flex-start; }
        .print-zone-vertical-center { justify-content: center; }
        .print-zone-vertical-bottom { justify-content: flex-end; }
        .print-zone-emphasized { font-weight: 700; }
        .sample-logo { align-items: center; border: 1px dashed #64748b; display: flex; font-size: .7em; height: 9mm; justify-content: center; width: 28mm; }
        .zone-document-title { font-size: 1em; }
        @media print { body { background: white; } .toolbar { display: none; } .page { box-shadow: none; margin: 0; } }
        @page { size: {{ $pageSettings['paper_size'] }} {{ $pageSettings['orientation'] }}; margin: 0; }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print / Save PDF</button></div>
    <main class="page">
        <div class="notice"><strong>Draft Preview</strong> - sample values only. This is not an approved controlled document.</div>
        <div class="print-header">@include('reports.partials.print-header', ['preview' => true, 'document' => null, 'organization' => [], 'issuance' => null, 'reportTemplate' => $reportTemplate])</div>
        <h1>{{ $template->name }}</h1>
        <div class="meta">{{ $template->code }} · Version {{ $version->version }} · {{ $template->department?->name ?? 'Department not assigned' }}</div>
        @if ($template->documentType)
            <div class="meta">Document type: {{ $template->documentType->name }}</div>
        @endif

        @if ($version->variables->isNotEmpty())
            <h2>Variables</h2>
            <table>
                <tbody>
                    @foreach ($version->variables as $variable)
                        <tr>
                            <td>{{ $variable->label ?: str($variable->name)->replace('_', ' ')->title() }}</td>
                            <td>{{ filled($variable->default_value) ? $variable->default_value : '[Sample value]' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h2>Document Content</h2>
        @forelse ($version->sections as $section)
            @php($sectionContent = $section->content ?? '')
            @foreach ($version->variables as $variable)
                @php($sampleValue = filled($variable->default_value) ? $variable->default_value : '[Sample value]')
                @php($sectionContent = str_replace(['{{'.$variable->name.'}}', '{{ '.$variable->name.' }}'], $sampleValue, $sectionContent))
            @endforeach
            <article class="section">
                <h3>{{ $section->section_order }}. {{ $section->title }}</h3>
                <div class="content">{!! filled($sectionContent) ? $sectionContent : '<p class="muted">No content entered.</p>' !!}</div>
            </article>
        @empty
            <p class="muted">No sections have been added to this draft version yet.</p>
        @endforelse
        <div class="print-footer">@include('reports.partials.print-footer', ['preview' => true, 'document' => null, 'organization' => [], 'issuance' => null, 'reportTemplate' => $reportTemplate])</div>
    </main>
</body>
</html>
