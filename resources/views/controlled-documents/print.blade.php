<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->document_number }} - {{ $document->title }}</title>
    @php($pageSettings = $reportTemplate->printPageSettings())
    @php($configuredFooterZones = $reportTemplate->printFooterZones())
    @php($pageNumberColumn = collect($configuredFooterZones['columns'])->first(fn (array $column): bool => collect($column['items'])->contains('token', 'page_number')))
    <style>
        :root {
            --primary: {{ $pageSettings['primary_color'] }};
            --secondary: {{ $pageSettings['secondary_color'] }};
            color: var(--primary);
            font-family: {{ match($pageSettings['font_family']) { 'times' => '"Times New Roman", serif', 'georgia' => 'Georgia, serif', default => 'Arial, Helvetica, sans-serif' } }};
            font-size: {{ $pageSettings['font_size'] }}pt;
            line-height: 1.5;
        }

        body {
            background: #eef2f6;
            margin: 0;
            padding: 24px;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            margin: 0 auto 16px;
            max-width: 900px;
        }

        button {
            background: #1f2937;
            border: 0;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font: inherit;
            padding: 9px 14px;
        }

        .page {
            background: #fff;
            box-shadow: 0 18px 48px rgba(15, 23, 42, .16);
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin: 0 auto;
            max-width: 900px;
            min-height: 1120px;
            padding: {{ $pageSettings['margin_top_mm'] }}mm {{ $pageSettings['margin_right_mm'] }}mm {{ $pageSettings['margin_bottom_mm'] }}mm {{ $pageSettings['margin_left_mm'] }}mm;
        }

        header {
            border-bottom: 2px solid var(--primary);
            margin-bottom: 24px;
        }

        h1 {
            font-size: 24px;
            margin: 0 0 8px;
        }

        h2 {
            border-bottom: 1px solid #d5dbe3;
            font-size: 16px;
            margin: 28px 0 12px;
            padding-bottom: 6px;
        }

        h3 {
            font-size: 14px;
            margin: 18px 0 8px;
        }

        table {
            border-collapse: collapse;
            margin: 0 0 14px;
            width: 100%;
        }

        th,
        td {
            border: {{ $pageSettings['show_table_borders'] ? '1px solid #d5dbe3' : '0' }};
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: var(--secondary);
            font-weight: 700;
            width: 28%;
        }

        .meta {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .section {
            break-inside: avoid;
            margin-top: 18px;
        }

        .content {
            border: 1px solid #d5dbe3;
            padding: 12px 14px;
        }

        .content :first-child {
            margin-top: 0;
        }

        .content :last-child {
            margin-bottom: 0;
        }

        .muted {
            color: #617182;
        }

        .watermark {
            border: 2px dashed #b45309;
            color: #92400e;
            font-weight: 700;
            letter-spacing: .08em;
            margin: 0 0 18px;
            padding: 10px 14px;
            text-align: center;
            text-transform: uppercase;
        }

        .reference-box {
            background: #f8fafc;
            border: 1px solid #d5dbe3;
            margin-bottom: 18px;
            padding: 12px 14px;
        }

        .print-grid {
            display: grid;
            grid-column: 1 / -1;
        }

        .print-grid-bordered {
            border: 1px solid var(--primary);
        }

        .print-zone {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-height: 56px;
            padding: 8px;
        }

        .print-grid-bordered .print-zone + .print-zone {
            border-left: 1px solid var(--primary);
        }

        .print-zone-center {
            align-items: center;
            text-align: center;
        }

        .print-zone-right {
            align-items: flex-end;
            text-align: right;
        }

        .print-zone-vertical-top {
            justify-content: flex-start;
        }

        .print-zone-vertical-center {
            justify-content: center;
        }

        .print-zone-vertical-bottom {
            justify-content: flex-end;
        }

        .organization-logo {
            max-height: 58px;
            max-width: 150px;
            object-fit: contain;
        }

        .zone-document-title {
            font-size: 16px;
        }

        @page {
            size: {{ $pageSettings['paper_size'] }} {{ $pageSettings['orientation'] }};
            margin: {{ $pageSettings['margin_top_mm'] }}mm {{ $pageSettings['margin_right_mm'] }}mm {{ $pageSettings['margin_bottom_mm'] }}mm {{ $pageSettings['margin_left_mm'] }}mm;
            @if ($pageNumberColumn)
                @switch($pageNumberColumn['alignment'])
                    @case('left')
                @bottom-left {
                        @break
                    @case('center')
                @bottom-center {
                        @break
                    @default
                @bottom-right {
                @endswitch
                    content: "Page " counter(page) " of " counter(pages);
                    color: {{ $pageSettings['primary_color'] }};
                    font-family: {{ match($pageSettings['font_family']) { 'times' => '"Times New Roman", serif', 'georgia' => 'Georgia, serif', default => 'Arial, Helvetica, sans-serif' } }};
                    font-size: 9pt;
                }
                @endif
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .page {
                box-shadow: none;
                margin: 0;
                max-width: none;
                min-height: auto;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

    <main class="page">
        @php($fieldOrder = collect($reportTemplate->fields)->pluck('key')->flip())
        @php($fieldConfig = collect($reportTemplate->fields)->keyBy('key'))
        @php($headerZones = $reportTemplate->printHeaderZones())
        @php($footerZones = $configuredFooterZones)

        <header
            class="print-grid {{ $headerZones['show_borders'] ? 'print-grid-bordered' : '' }}"
            style="gap: {{ $headerZones['gap_mm'] }}mm; grid-template-columns: {{ collect($headerZones['columns'])->pluck('width')->map(fn ($width) => $width.'fr')->implode(' ') }}; order: -3;"
        >
            @foreach ($headerZones['columns'] as $column)
                @include('reports.partials.print-zone', ['items' => $column['items'], 'alignment' => $column['alignment'], 'verticalAlignment' => $column['vertical_alignment'], 'document' => $document, 'organization' => $organization, 'issuance' => $issuance, 'reportTemplate' => $reportTemplate])
            @endforeach
        </header>

        @if ($issuance)
            <div class="watermark" style="grid-column: 1 / -1; order: -2;">
                Controlled Copy {{ $issuance->copy_number }} | {{ $issuance->watermark_code }} | Issued {{ $issuance->issued_at->toDayDateTimeString() }}
            </div>
        @endif

        @if ($document->referenced_sop_number)
            <section class="reference-box" style="grid-column: 1 / -1;">
                <strong>Referenced SOP:</strong>
                {{ $document->referenced_sop_number }}
                v{{ $document->referenced_sop_version }}
                @if ($document->referenced_sop_effective_date)
                    (Effective {{ $document->referenced_sop_effective_date->toFormattedDateString() }})
                @endif
            </section>
        @endif

        @if (in_array('organization', $enabledFields, true) && (! ($fieldConfig['organization']['hide_when_empty'] ?? false) || $organization !== []))
            <section class="reference-box" style="grid-column: {{ ($fieldConfig['organization']['width'] ?? 'full') === 'full' ? '1 / -1' : 'span 1' }}; order: {{ $fieldOrder['organization'] ?? 0 }}; {{ ($fieldConfig['organization']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
                @if (filled($organization['logo_path'] ?? null))
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($organization['logo_path']) }}"
                        alt="{{ $organization['legal_name'] ?? 'Organization' }} logo"
                        style="float: right; max-height: 64px; max-width: 180px;"
                    >
                @endif
                <strong>{{ $organization['legal_name'] ?? '-' }}</strong>
                @if (filled($organization['display_name'] ?? null) && $organization['display_name'] !== ($organization['legal_name'] ?? null))
                    <div>{{ $organization['display_name'] }}</div>
                @endif
                <div class="muted">
                    {{ collect([
                        $organization['address_line_1'] ?? null,
                        $organization['address_line_2'] ?? null,
                        $organization['city'] ?? null,
                        $organization['state'] ?? null,
                        $organization['postal_code'] ?? null,
                        $organization['country_code'] ?? null,
                    ])->filter()->implode(', ') }}
                </div>
                @if (filled($organization['registration_number'] ?? null))
                    <div class="muted">Registration: {{ $organization['registration_number'] }}</div>
                @endif
                @if (filled($organization['document_header'] ?? null))
                    <div>{{ $organization['document_header'] }}</div>
                @endif
            </section>
        @endif

        @if (in_array('document_identity', $enabledFields, true))
        <header style="grid-column: {{ ($fieldConfig['document_identity']['width'] ?? 'full') === 'full' ? '1 / -1' : 'span 1' }}; order: {{ $fieldOrder['document_identity'] ?? 0 }}; {{ ($fieldConfig['document_identity']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
            <h1>{{ $document->title }}</h1>
            <div class="muted">
                {{ $document->document_number }} | Version {{ $document->version }}
                @if (in_array('status', $enabledFields, true))
                    | {{ $document->documentStatus->name }}
                @endif
            </div>
        </header>
        @endif


        @if (in_array('variables', $enabledFields, true) && (! ($fieldConfig['variables']['hide_when_empty'] ?? false) || $document->variables->isNotEmpty()))
            <section style="grid-column: {{ ($fieldConfig['variables']['width'] ?? 'full') === 'full' ? '1 / -1' : 'span 1' }}; order: {{ $fieldOrder['variables'] ?? 0 }}; {{ ($fieldConfig['variables']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
            <h2>{{ $fieldConfig['variables']['label'] ?? 'Variables' }}</h2>
            <table>
                <tbody>
                    @forelse ($document->variables as $variable)
                        <tr>
                            <th>{{ str($variable->variable_name)->replace('_', ' ')->title() }}</th>
                            <td>{{ filled($variable->value) ? $variable->value : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td>No variable values recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </section>
        @endif

        @if (in_array('sections', $enabledFields, true))
        <section style="grid-column: {{ ($fieldConfig['sections']['width'] ?? 'full') === 'full' ? '1 / -1' : 'span 1' }}; order: {{ $fieldOrder['sections'] ?? 0 }}; {{ ($fieldConfig['sections']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
        <h2>{{ $fieldConfig['sections']['label'] ?? 'Sections' }}</h2>
        @forelse ($document->sections as $section)
            <article class="section">
                <h3>{{ $section->section_order }}. {{ $section->title }}</h3>
                <div class="content">
                    {!! filled($section->content) ? $section->content : '<p>-</p>' !!}
                </div>
            </article>
        @empty
            <p class="muted">No sections have been added to this SOP document.</p>
        @endforelse
        </section>
        @endif

        @if (in_array('approvals', $enabledFields, true) && (! ($fieldConfig['approvals']['hide_when_empty'] ?? false) || $document->approvals->isNotEmpty()))
            <section style="grid-column: {{ ($fieldConfig['approvals']['width'] ?? 'full') === 'full' ? '1 / -1' : 'span 1' }}; order: {{ $fieldOrder['approvals'] ?? 0 }}; {{ ($fieldConfig['approvals']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
            <h2>{{ $fieldConfig['approvals']['label'] ?? 'Approvals' }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>Step</th>
                        <th>Department</th>
                        <th>Decision</th>
                        <th>Approver</th>
                        <th>Approved At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($document->approvals as $approval)
                        <tr>
                            <td>{{ $approval->workflowStep?->approvalStepType->name  ?? '-' }}</td>
                            <td>{{ $approval->workflowStep?->department?->name ?? $document->department?->name ?? '-' }}</td>
                            <td>{{ $approval->approvalDecision?->name ?? '-' }}</td>
                            <td>{{ $approval->approver?->name ?? '-' }}</td>
                            <td>{{ $approval->approved_at?->toDayDateTimeString() ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No approval signatures recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </section>
        @endif

        @if (in_array('audit_reference', $enabledFields, true))
            <footer class="muted" style="order: {{ $fieldOrder['audit_reference'] ?? 0 }}; margin-top: 24px;">
                Printed by {{ auth()->user()->name }} at {{ now()->toDayDateTimeString() }} · Template {{ $reportTemplate->layout_key }}
            </footer>
        @endif

        @if (filled($organization['document_footer'] ?? null) && in_array('footer', $enabledFields, true))
            <footer class="muted" style="grid-column: 1 / -1; order: {{ $fieldOrder['footer'] ?? 0 }}; margin-top: 28px; text-align: center;">
                {{ $organization['document_footer'] }}
            </footer>
        @endif

        <footer
            class="print-grid {{ $footerZones['show_borders'] ? 'print-grid-bordered' : '' }}"
            style="gap: {{ $footerZones['gap_mm'] }}mm; grid-template-columns: {{ collect($footerZones['columns'])->pluck('width')->map(fn ($width) => $width.'fr')->implode(' ') }}; order: 9999;"
        >
            @foreach ($footerZones['columns'] as $column)
                @include('reports.partials.print-zone', ['items' => $column['items'], 'alignment' => $column['alignment'], 'verticalAlignment' => $column['vertical_alignment'], 'document' => $document, 'organization' => $organization, 'issuance' => $issuance, 'reportTemplate' => $reportTemplate])
            @endforeach
        </footer>
    </main>
</body>
</html>
