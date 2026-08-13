<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->document_number }} - {{ $document->title }}</title>
    @php($pageSettings = $reportTemplate->printPageSettings())
    @php($configuredHeaderZones = $reportTemplate->printHeaderZones())
    @php($configuredFooterZones = $reportTemplate->printFooterZones())
    @php($pageNumberColumn = collect($configuredFooterZones['rows'])->flatMap(fn (array $row) => $row['cells'])->first(fn (array $cell): bool => collect($cell['items'])->contains('token', 'page_number')))
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
            padding: 2px 2px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: var(--secondary);
            font-weight: 700;
            width: 28%;
        }

        .approval-signatures {
            break-inside: auto;
            table-layout: fixed;
        }

        .approval-signatures .signature-group {
            break-inside: avoid;
        }

        .approval-signatures th,
        .approval-signatures td {
            width: auto;
        }

        .approval-signatures .signature-group-heading {
            background: #d1d5db;
            color: #111827;
            font-weight: 700;
            padding: 4px 8px;
        }

        .approval-signatures .signature-department {
            background: #fff;
            font-weight: 600;
            vertical-align: middle;
            width: 28%;
        }

        .approval-signatures .signature-label {
            background: #fff;
            font-weight: 600;
            width: 18%;
        }

        .approval-signatures .signature-value {
            min-height: 22px;
            width: 54%;
        }

        .approval-signatures .signature-sign {
            min-height: 36px;
        }

        .approval-signatures .signature-sign div + div {
            margin-top: 2px;
        }

        .signature-manifestation-note {
            font-size: 0.9em;
            margin-top: -6px;
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

        .section-format-structured-table,
        .section-format-checklist,
        .section-format-repeating-log {
            break-inside: auto;
        }

        .execution-table-title {
            break-after: avoid;
            font-weight: 700;
            margin: 8px 0 4px;
        }

        .execution-table-block {
            break-inside: avoid;
        }

        .section-format-structured-table .content thead,
        .section-format-checklist .content thead,
        .section-format-repeating-log .content thead {
            display: table-header-group;
        }

        .section-format-structured-table .content table,
        .section-format-checklist .content table,
        .section-format-repeating-log .content table,
        .section-format-signatures .content table,
        .section-format-annexures .content table {
            width: 100%;
            break-inside: auto;
        }

        .section-format-checklist .content input[type='checkbox'] {
            width: 4mm;
            height: 4mm;
        }

        .section-format-annexures {
            break-before: page;
        }

        .content {
            border: {{ $pageSettings['show_table_borders'] ? '1px solid #d5dbe3' : '0' }};
            padding: 2px 14px;
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
            border: 1px dashed #b45309;
            color: #92400e;
            font-weight: 350;
            letter-spacing: .06em;
            margin: 0 0 18px;
            padding: 8px 10px;
            text-align: center;
            text-transform: uppercase;
        }

        .reference-box {
            background: #f8fafc;
            border: 1px solid #d5dbe3;
            margin-bottom: 18px;
            padding: 12px 14px;
        }

        .table-of-contents { break-inside: avoid; grid-column: 1 / -1; }
        .toc-entry { align-items: baseline; border-bottom: 1px dotted #94a3b8; display: flex; justify-content: space-between; padding: 4px 0; }
        .toc-entry.level-2 { padding-left: 16px; }
        .toc-entry.level-3 { padding-left: 32px; }
        .toc-entry.level-4 { padding-left: 48px; }
        .toc-entry.level-5 { padding-left: 64px; }
        .toc-entry.level-6 { padding-left: 80px; }
        .toc-entry a { color: inherit; text-decoration: none; }
        .toc-entry > span { margin-left: auto; padding-left: 12px; text-align: right; }
        .title-page { align-items: center; break-inside: avoid; display: flex; flex-direction: column; grid-column: 1 / -1; height: 200mm; justify-content: center; overflow: hidden; padding: 10mm 14mm; text-align: center; }
        .title-page-logo { max-height: 28mm; max-width: 60mm; object-fit: contain; }
        .title-page-organization { font-size: 1.25em; font-weight: 700; letter-spacing: .04em; margin-top: 7mm; text-transform: uppercase; }
        .title-page h1 { border: 0; font-size: 1.9em; line-height: 1.2; margin: 16mm 0 5mm; max-width: 155mm; }
        .title-page-subtitle { color: #617182; font-size: 1.1em; font-style: italic; max-width: 140mm; }
        .title-page-identity { border: 1px solid #cbd5e1; display: grid; gap: 0; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 10mm; max-width: 145mm; text-align: left; width: 100%; }
        .title-page-identity span { border-bottom: 1px solid #cbd5e1; padding: 8px 12px; }
        .title-page-identity span:nth-child(odd) { background: #f8fafc; font-weight: 700; }
        .title-page-notice { border: 1px solid var(--primary); font-size: .9em; font-weight: 700; letter-spacing: .12em; margin-top: 12mm; padding: 8px 20px; }
        .toc-marker { color: transparent; font-size: 1px; }

        .print-zone {
            display: flex;
            flex-direction: column;
            gap: 4px;
            /* min-height: 56px;    */
            padding: 8px;
        }

        .print-table {
            border-bottom: 0;
            display: flex;
            flex-direction: column;
            grid-column: 1 / -1;
            margin-bottom: 0;
        }

        .print-table-row {
            display: grid;
        }

        .print-table-bordered {
            border-left: 1px solid var(--primary);
            border-top: 1px solid var(--primary);
        }

        .print-table-bordered .print-zone {
            border-bottom: 1px solid var(--primary);
            border-right: 1px solid var(--primary);
        }

        .print-header-flow {
            grid-column: 1 / -1;
            order: -3;
        }

        .print-footer-flow {
            grid-column: 1 / -1;
            order: 9999;
        }

        .print-document-frame,
        .print-document-frame > tbody,
        .print-document-frame > tbody > tr,
        .print-document-frame > tbody > tr > td {
            border: 0;
            display: block;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        .print-document-header {
            display: none;
        }

        .print-document-footer {
            display: none;
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

        .print-zone-emphasized {
            font-weight: 700;
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
            @if (($serverPdf ?? false) && isset($serverPdfMargins))
                margin: {{ $serverPdfMargins['top'] }}mm {{ $serverPdfMargins['right'] }}mm {{ $serverPdfMargins['bottom'] }}mm {{ $serverPdfMargins['left'] }}mm;
            @else
                margin: {{ $pageSettings['margin_top_mm'] }}mm {{ $pageSettings['margin_right_mm'] }}mm {{ $pageSettings['margin_bottom_mm'] }}mm {{ $pageSettings['margin_left_mm'] }}mm;
            @endif
            @if ($pageNumberColumn && ! ($serverPdf ?? false))
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

            .print-document-frame {
                border-collapse: collapse;
                display: table;
                table-layout: fixed;
                width: 100%;
            }

            .print-document-frame > .print-document-header {
                display: table-header-group;
            }

            .print-document-frame > .print-document-header > tr {
                display: table-row;
            }

            .print-document-frame > .print-document-header > tr > td {
                border: 0;
                display: table-cell;
                padding: 0 0 {{ $configuredHeaderZones['content_gap_mm'] }}mm;
                vertical-align: top;
            }

            .print-document-frame > .print-document-footer {
                display: table-footer-group;
            }

            .print-document-frame > .print-document-footer > tr {
                display: table-row;
            }

            .print-document-frame > .print-document-footer > tr > td {
                border: 0;
                display: table-cell;
                padding: {{ $configuredFooterZones['content_gap_mm'] }}mm 0 0;
                vertical-align: bottom;
            }

            .print-document-frame > tbody {
                display: table-row-group;
            }

            .print-document-frame > tbody > tr {
                display: table-row;
            }

            .print-document-frame > tbody > tr > td {
                border: 0;
                display: table-cell;
                padding: 0;
                vertical-align: top;
            }

            .print-header-flow-hidden {
                display: none;
            }

            .print-footer-flow-hidden {
                display: none;
            }
        }
    </style>
</head>
<body>
    @unless ($serverPdf ?? false)
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>
    @endunless

    @php($headerZones = $configuredHeaderZones)
    @php($footerZones = $configuredFooterZones)
    @php($toc = $reportTemplate->tocConfiguration())
    @php($showToc = $toc['enabled'])
    @php($titlePage = $reportTemplate->titlePageConfiguration())

    <table class="print-document-frame">
        @if ($headerZones['repeat_every_page'] && ! ($serverPdf ?? false))
            <thead class="print-document-header">
                <tr>
                    <td>@include('reports.partials.print-header')</td>
                </tr>
            </thead>
        @endif
        <tbody>
            <tr>
                <td>
    <main class="page">
        @php($fieldOrder = collect($reportTemplate->fields)->pluck('key')->flip())
        @php($fieldConfig = collect($reportTemplate->fields)->keyBy('key'))
        <div class="print-header-flow {{ ($serverPdf ?? false) || $headerZones['repeat_every_page'] ? 'print-header-flow-hidden' : '' }}">
            @include('reports.partials.print-header')
        </div>

        @if ($titlePage['enabled'])
            @include('controlled-documents.partials.title-page')
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
                    | {{ $document->documentStatus?->name ?? '-' }}
                @endif
            </div>
            @if (filled($issuance?->execution?->batch_number) || filled($issuance?->execution?->product_name))
                <div class="meta" style="margin-top: 8px;">
                    @if (filled($issuance?->execution?->product_name))
                        <div><strong>Product:</strong> {{ $issuance->execution->product_name }}</div>
                    @endif
                    @if (filled($issuance?->execution?->batch_number))
                        <div><strong>Batch:</strong> {{ $issuance->execution->batch_number }}</div>
                    @endif
                </div>
            @endif
            @if (filled($document->purpose))
                <div style="margin-top: 8px;"><strong>Purpose:</strong> {{ $document->purpose }}</div>
            @endif
        </header>
        @endif

        @if (in_array('issuance_number', $enabledFields, true) && (! ($fieldConfig['issuance_number']['hide_when_empty'] ?? false) || filled($issuance?->issuance_number)))
            <section class="meta" style="grid-column: {{ ($fieldConfig['issuance_number']['width'] ?? 'full') === 'full' ? '1 / -1' : 'span 1' }}; order: {{ $fieldOrder['issuance_number'] ?? 0 }}; {{ ($fieldConfig['issuance_number']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
                @if ($fieldConfig['issuance_number']['show_label'] ?? true)
                    <strong>{{ $fieldConfig['issuance_number']['label'] ?? 'Issuance Number' }}:</strong>
                @endif
                {{ $issuance?->issuance_number ?? '-' }}
            </section>
        @endif

        @if ($showToc && $toc['position'] === 'after_identity')
            @include('controlled-documents.partials.table-of-contents')
        @endif

        @if (in_array('variables', $enabledFields, true) && (! ($fieldConfig['variables']['hide_when_empty'] ?? false) || $document->variables->isNotEmpty()))
            <section style="grid-column: {{ ($fieldConfig['variables']['width'] ?? 'full') === 'full' ? '1 / -1' : 'span 1' }}; order: {{ $fieldOrder['variables'] ?? 0 }}; {{ ($fieldConfig['variables']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
        @if ($fieldConfig['variables']['show_label'] ?? true)<h2>{{ $fieldConfig['variables']['label'] ?? 'Variables' }}</h2>@endif
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

        @if ($showToc && $toc['position'] === 'before_sections')
            @include('controlled-documents.partials.table-of-contents')
        @endif

        @if (in_array('approvals', $enabledFields, true) && (! ($fieldConfig['approvals']['hide_when_empty'] ?? false) || $document->approvals->isNotEmpty()))
            <section style="grid-column: {{ ($fieldConfig['approvals']['width'] ?? 'full') === 'full' ? '1 / -1' : 'span 1' }}; order: {{ $fieldOrder['approvals'] ?? 0 }}; {{ ($fieldConfig['approvals']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
            @if ($fieldConfig['approvals']['show_label'] ?? true)<h2>{{ $fieldConfig['approvals']['label'] ?? 'Approvals' }}</h2>@endif
            @include('controlled-documents.partials.approval-signatures')
            </section>
        @endif

        @if (in_array('sections', $enabledFields, true))
        <section style="grid-column: {{ ($fieldConfig['sections']['width'] ?? 'full') === 'full' ? '1 / -1' : 'span 1' }}; order: {{ $fieldOrder['sections'] ?? 0 }}; {{ ($fieldConfig['sections']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
        @if ($fieldConfig['sections']['show_label'] ?? true)<h2>{{ $fieldConfig['sections']['label'] ?? 'Sections' }}</h2>@endif
        @php($printSections = $issuance?->execution?->sections ?? $document->sections)
        @forelse ($printSections as $section)
            <article id="section-{{ $section->getKey() }}" class="section section-format-{{ str($section->section_type ?? 'rich_text')->replace('_', '-')->lower() }}">
                @if ($fieldConfig['sections']['show_section_titles'] ?? true)<h3>
                    @if ($tocMarkerMode ?? false)
                        <span class="toc-marker">{{ app(\App\Domain\DMS\Services\DocumentTocPageResolver::class)->marker($section->getKey()) }}</span>
                    @endif
                    {{ $section->section_order }}. {{ $section->title }}
                </h3>@endif
                <div class="content">
                    {!! filled($section->content) ? $section->content : '<p>-</p>' !!}
                </div>
                @if ($issuance?->isExecution() && $section->items->isNotEmpty())
                    @include('controlled-documents.partials.execution-table', ['section' => $section])
                @endif
            </article>
        @empty
            <p class="muted">No sections have been added to this SOP document.</p>
        @endforelse
        </section>
        @endif

        @php($printAttachments = $issuance?->execution?->attachments ?? $document->attachments)
        @if ($printAttachments->isNotEmpty())
            <section style="grid-column: 1 / -1; order: {{ ($fieldOrder['sections'] ?? 0) + 1 }}; break-before: page;">
                <h2>Annexure Index</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Annexure</th>
                            <th>File</th>
                            <th>Role</th>
                            <th>Required</th>
                            <th>Included in Print</th>
                            <th>Integrity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($printAttachments as $attachment)
                            <tr>
                                <td>{{ $attachment->annexure_number ?? '-' }}</td>
                                <td>{{ $attachment->original_name }}</td>
                                <td>{{ str($attachment->attachment_role)->replace('_', ' ')->title() }}</td>
                                <td>{{ $attachment->is_required ? 'Yes' : 'No' }}</td>
                                <td>{{ $attachment->include_in_print ? 'Yes' : 'No' }}</td>
                                <td>{{ app(\App\Domain\QMS\Services\QualityAttachmentIntegrityService::class)->status($attachment)->value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        @if (in_array('audit_reference', $enabledFields, true))
            <footer class="muted" style="order: {{ $fieldOrder['audit_reference'] ?? 0 }}; margin-top: 24px;">
                Printed by {{ auth()->user()->name }} at {{ app(\App\Support\Formatting\DateFormatSettings::class)->formatDateTime(now()) }} · Template {{ $reportTemplate->layout_key }}
            </footer>
        @endif

        @if (filled($organization['document_footer'] ?? null) && in_array('footer', $enabledFields, true))
            <footer class="muted" style="grid-column: 1 / -1; order: {{ $fieldOrder['footer'] ?? 0 }}; margin-top: 28px; text-align: center;">
                {{ $organization['document_footer'] }}
            </footer>
        @endif

        <div class="print-footer-flow {{ ($serverPdf ?? false) || $footerZones['repeat_every_page'] ? 'print-footer-flow-hidden' : '' }}">
            @include('reports.partials.print-footer')
        </div>
    </main>
                </td>
            </tr>
        </tbody>
        @if ($footerZones['repeat_every_page'] && ! ($serverPdf ?? false))
            <tfoot class="print-document-footer">
                <tr>
                    <td>@include('reports.partials.print-footer')</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
