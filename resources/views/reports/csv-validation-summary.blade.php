<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $project->project_number }} - {{ $template->name }}</title>
    @php($settings = $template->printPageSettings())
    <style>
        @page {
            size: {{ $settings['paper_size'] }} {{ $settings['orientation'] }};
            margin: {{ $settings['margin_top_mm'] }}mm {{ $settings['margin_right_mm'] }}mm {{ $settings['margin_bottom_mm'] }}mm {{ $settings['margin_left_mm'] }}mm;
            @bottom-right { content: "Page " counter(page) " of " counter(pages); }
        }
        body { color: {{ $settings['primary_color'] }}; font: {{ $settings['font_size'] }}pt/1.45 Arial, sans-serif; margin: 24px auto; max-width: 960px; }
        .toolbar { text-align: right; } button { background: {{ $settings['primary_color'] }}; border: 0; color: white; padding: 9px 14px; }
        h1 { border-bottom: 2px solid {{ $settings['primary_color'] }}; padding-bottom: 12px; }
        h2 { border-bottom: 1px solid #cbd5e1; font-size: 15px; margin-top: 24px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; vertical-align: top; }
        th { background: {{ $settings['secondary_color'] }}; width: 28%; }
        .attribution { color: #64748b; margin-top: 24px; }
        @media print { .toolbar { display: none; } body { margin: 0; max-width: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print / Save PDF</button></div>
    <h1>Computerized System Validation Summary</h1>

    <table>
        <tbody>
        @foreach ($template->fields as $field)
            @if (($field['enabled'] ?? false) && $field['key'] !== 'audit_events')
                @php($value = app(\App\Domain\Reporting\Support\ReportFieldRegistry::class)->value($project, $field['key']))
                @if (! ($field['hide_when_empty'] ?? false) || filled($value))
                    <tr class="{{ ($field['page_break_before'] ?? false) ? 'page-break' : '' }}">
                        <th>{{ $field['label'] }}</th>
                        <td>{!! nl2br(e($value ?: '-')) !!}</td>
                    </tr>
                @endif
            @endif
        @endforeach
        </tbody>
    </table>

    @if (in_array('audit_events', $enabledFields, true))
        <h2>Signed Lifecycle Audit Trail</h2>
        <table>
            <thead><tr><th>Time</th><th>Transition</th><th>Actor</th><th>Reason</th><th>Signature</th></tr></thead>
            <tbody>
            @forelse ($project->auditEvents as $event)
                <tr>
                    <td>{{ $event->occurred_at?->toDayDateTimeString() ?? '-' }}</td>
                    <td>{{ $event->from_status?->value ?? 'created' }} → {{ $event->to_status->value }}</td>
                    <td>{{ $event->actor?->name ?? '-' }}</td>
                    <td>{{ $event->reason ?? '-' }}</td>
                    <td>{{ $event->signature_hash ? 'Electronically signed' : 'Audit event' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No lifecycle events recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    @endif

    <p class="attribution">
        Generated {{ now()->toDayDateTimeString() }} by {{ auth()->user()->name }} ·
        Template {{ $template->layout_key }} · ALCOA+ traceable export
    </p>
</body>
</html>
