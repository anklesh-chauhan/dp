<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $previewLabel }} - {{ $template->name }}</title>
    @php($portraitWidth = $pageSettings['paper_size'] === 'letter' ? 215.9 : 210)
    @php($portraitHeight = $pageSettings['paper_size'] === 'letter' ? 279.4 : 297)
    @php($pageWidth = $pageSettings['orientation'] === 'landscape' ? $portraitHeight : $portraitWidth)
    @php($pageHeight = $pageSettings['orientation'] === 'landscape' ? $portraitWidth : $portraitHeight)
    <style>
        @page { size: A4; margin: 18mm; }
        * { box-sizing: border-box; }
        body { background: #e5e7eb; color: {{ $pageSettings['primary_color'] }}; font-family: {{ match($pageSettings['font_family']) { 'times' => '"Times New Roman", serif', 'georgia' => 'Georgia, serif', default => 'Arial, Helvetica, sans-serif' } }}; font-size: {{ $pageSettings['font_size'] }}pt; margin: 0; }
        .toolbar { align-items: center; background: #111827; color: white; display: flex; justify-content: space-between; padding: 12px 20px; position: sticky; top: 0; z-index: 10; }
        .toolbar-meta { font-size: 13px; }
        button { background: #2563eb; border: 0; border-radius: 6px; color: white; cursor: pointer; padding: 9px 14px; }
        .page { background: white; box-shadow: 0 2px 12px #0002; display: grid; gap: 5mm; grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 28px auto; min-height: {{ $pageHeight }}mm; padding: {{ $pageSettings['margin_top_mm'] }}mm {{ $pageSettings['margin_right_mm'] }}mm {{ $pageSettings['margin_bottom_mm'] }}mm {{ $pageSettings['margin_left_mm'] }}mm; width: {{ $pageWidth }}mm; }
        .notice { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; color: #92400e; margin-bottom: 24px; padding: 12px 16px; }
        h1 { border-bottom: 2px solid #1f2937; margin-bottom: 6px; padding-bottom: 10px; }
        h2 { border-bottom: 1px solid #d1d5db; margin-top: 34px; padding-bottom: 6px; }
        h3 { margin-bottom: 8px; }
        .meta, .muted { color: #6b7280; font-size: 13px; }
        .section { page-break-inside: avoid; }
        .print-block { break-inside: avoid; }
        .print-block-full, .notice, .print-header, .print-footer { grid-column: 1 / -1; }
        .content table, .print-block table { border-collapse: collapse; width: 100%; }
        .content td, .content th, .print-block td, .print-block th { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
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
    <div class="toolbar">
        <div class="toolbar-meta"><strong>{{ $previewLabel }}</strong> using saved print template: {{ $reportTemplate->name }}</div>
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>
    <main class="page">
        @php($fieldOrder = collect($reportTemplate->fields)->pluck('key')->flip())
        @php($fieldConfig = collect($reportTemplate->fields)->keyBy('key'))
        @php($enabledFields = $reportTemplate->enabledFieldKeys())

        <div class="notice"><strong>{{ $previewLabel }}</strong> of version {{ $version->version }} using “{{ $reportTemplate->name }}”. Sample values only; this is not an approved controlled document.</div>
        <div class="print-header">@include('reports.partials.print-header', ['preview' => true, 'document' => null, 'organization' => [], 'issuance' => null, 'reportTemplate' => $reportTemplate])</div>

        @if (in_array('organization', $enabledFields, true))
            <section class="print-block {{ ($fieldConfig['organization']['width'] ?? 'full') === 'full' ? 'print-block-full' : '' }}" style="order: {{ $fieldOrder['organization'] ?? 0 }}; {{ ($fieldConfig['organization']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
                @if ($fieldConfig['organization']['show_label'] ?? true)<h2>{{ $fieldConfig['organization']['label'] }}</h2>@endif
                <div class="muted">The controlled document’s organization snapshot and branding will appear here.</div>
            </section>
        @endif

        @if (in_array('document_identity', $enabledFields, true))
        <header class="print-block {{ ($fieldConfig['document_identity']['width'] ?? 'full') === 'full' ? 'print-block-full' : '' }}" style="order: {{ $fieldOrder['document_identity'] ?? 0 }}; {{ ($fieldConfig['document_identity']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
        @if ($fieldConfig['document_identity']['show_label'] ?? true)<h2>{{ $fieldConfig['document_identity']['label'] }}</h2>@endif
        <h1>{{ $template->name }}</h1>
        <div class="meta">{{ $template->code }} · Version {{ $version->version }} · {{ $template->department?->name ?? 'Department not assigned' }}</div>
        @if ($template->documentType)
            <div class="meta">Document type: {{ $template->documentType->name }}</div>
        @endif
        </header>
        @endif

        @foreach (['status', 'department', 'owner', 'effective_date', 'review_date'] as $fieldKey)
            @if (in_array($fieldKey, $enabledFields, true))
                @php($field = $fieldConfig[$fieldKey])
                @php($fieldValue = match ($fieldKey) {
                    'status' => $version->approval_status->label(),
                    'department' => $template->department?->name ?? 'Department not assigned',
                    'owner' => $template->creator?->name ?? 'Template author',
                    'effective_date' => $version->effective_date?->toFormattedDateString() ?? 'Assigned when published',
                    default => 'Assigned when the controlled document is created',
                })
                <section class="print-block {{ ($field['width'] ?? 'full') === 'full' ? 'print-block-full' : '' }}" style="order: {{ $fieldOrder[$fieldKey] ?? 0 }}; {{ ($field['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
                    @if ($field['show_label'] ?? true)<h2>{{ $field['label'] }}</h2>@endif
                    <div>{{ $fieldValue }}</div>
                </section>
            @endif
        @endforeach

        @if (in_array('variables', $enabledFields, true) && (! ($fieldConfig['variables']['hide_when_empty'] ?? false) || $version->variables->isNotEmpty()))
            <section class="print-block {{ ($fieldConfig['variables']['width'] ?? 'full') === 'full' ? 'print-block-full' : '' }}" style="order: {{ $fieldOrder['variables'] ?? 0 }}; {{ ($fieldConfig['variables']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
            @if ($fieldConfig['variables']['show_label'] ?? true)<h2>{{ $fieldConfig['variables']['label'] }}</h2>@endif
            <table>
                <tbody>
                    @forelse ($version->variables as $variable)
                        <tr>
                            <td>{{ $variable->label ?: str($variable->name)->replace('_', ' ')->title() }}</td>
                            <td>{{ filled($variable->default_value) ? $variable->default_value : '[Sample value]' }}</td>
                        </tr>
                    @empty
                        <tr><td>No variables have been added to this version.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </section>
        @endif

        @if (in_array('sections', $enabledFields, true))
        <section class="print-block {{ ($fieldConfig['sections']['width'] ?? 'full') === 'full' ? 'print-block-full' : '' }}" style="order: {{ $fieldOrder['sections'] ?? 0 }}; {{ ($fieldConfig['sections']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
        @if ($fieldConfig['sections']['show_label'] ?? true)<h2>{{ $fieldConfig['sections']['label'] }}</h2>@endif
        @forelse ($version->sections as $section)
            @php($sectionContent = $section->content ?? '')
            @foreach ($version->variables as $variable)
                @php($sampleValue = filled($variable->default_value) ? $variable->default_value : '[Sample value]')
                @php($sectionContent = str_replace(['{{'.$variable->name.'}}', '{{ '.$variable->name.' }}'], $sampleValue, $sectionContent))
            @endforeach
            <article class="section">
                @if ($fieldConfig['sections']['show_section_titles'] ?? true)<h3>{{ $section->section_order }}. {{ $section->title }}</h3>@endif
                <div class="content">{!! filled($sectionContent) ? $sectionContent : '<p class="muted">No content entered.</p>' !!}</div>
            </article>
        @empty
            <p class="muted">No sections have been added to this version yet.</p>
        @endforelse
        </section>
        @endif

        @if (in_array('approvals', $enabledFields, true) && (! ($fieldConfig['approvals']['hide_when_empty'] ?? false) || $version->approvalInstances->isNotEmpty()))
            <section class="print-block {{ ($fieldConfig['approvals']['width'] ?? 'full') === 'full' ? 'print-block-full' : '' }}" style="order: {{ $fieldOrder['approvals'] ?? 0 }}; {{ ($fieldConfig['approvals']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
                @if ($fieldConfig['approvals']['show_label'] ?? true)<h2>{{ $fieldConfig['approvals']['label'] }}</h2>@endif
                <table>
                    <thead><tr><th>Step</th><th>Decision</th><th>Decided By</th><th>Decided At</th></tr></thead>
                    <tbody>
                    @forelse ($version->approvalInstances as $approval)
                        <tr>
                            <td>{{ $approval->workflowStep?->approvalStepType?->name ?? 'Workflow step' }}</td>
                            <td>{{ str($approval->decision_code)->replace('_', ' ')->title() }}</td>
                            <td>{{ $approval->decider?->name ?? 'Pending' }}</td>
                            <td>{{ $approval->decided_at?->toDayDateTimeString() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No approval decisions have been recorded for this version.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>
        @endif

        @if (in_array('audit_reference', $enabledFields, true))
            <footer class="print-block muted {{ ($fieldConfig['audit_reference']['width'] ?? 'full') === 'full' ? 'print-block-full' : '' }}" style="order: {{ $fieldOrder['audit_reference'] ?? 0 }}; {{ ($fieldConfig['audit_reference']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
                Previewed by {{ auth()->user()->name }} at {{ now()->toDayDateTimeString() }} · Print template {{ $reportTemplate->name }}
            </footer>
        @endif

        @if (in_array('footer', $enabledFields, true))
            <footer class="print-block muted {{ ($fieldConfig['footer']['width'] ?? 'full') === 'full' ? 'print-block-full' : '' }}" style="order: {{ $fieldOrder['footer'] ?? 0 }}; {{ ($fieldConfig['footer']['page_break_before'] ?? false) ? 'break-before: page;' : '' }}">
                Organization footer content will appear here on the controlled document.
            </footer>
        @endif

        <div class="print-footer">@include('reports.partials.print-footer', ['preview' => true, 'document' => null, 'organization' => [], 'issuance' => null, 'reportTemplate' => $reportTemplate])</div>
    </main>
</body>
</html>
