<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $changeControl->change_number }} - {{ $template->name }}</title>
    <style>
        body { color: #172033; font: 13px/1.5 Arial, sans-serif; margin: 24px auto; max-width: 900px; }
        .toolbar { text-align: right; } button { background: #172033; border: 0; color: white; padding: 9px 14px; }
        h1 { border-bottom: 2px solid #172033; padding-bottom: 12px; }
        h2 { border-bottom: 1px solid #cbd5e1; font-size: 16px; margin-top: 26px; }
        table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; }
        @media print { .toolbar { display: none; } body { margin: 0; max-width: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print / Save PDF</button></div>
    <h1>Change Control Investigation Report</h1>
    @php($simpleFields = [
        'change_number' => ['Change Number', $changeControl->change_number],
        'title' => ['Title', $changeControl->title],
        'status' => ['Status', str($changeControl->status->value)->replace('_', ' ')->title()],
        'department' => ['Department', $changeControl->department?->name ?? '-'],
        'requester' => ['Requested By', $changeControl->requester?->name ?? '-'],
        'owner' => ['Owner', $changeControl->owner?->name ?? '-'],
    ])
    <table>
        <tbody>
        @foreach ($template->fields as $field)
            @if ($field['enabled'] && isset($simpleFields[$field['key']]))
                @php([$label, $value] = $simpleFields[$field['key']])
                <tr><th>{{ $label }}</th><td>{{ $value }}</td></tr>
            @endif
        @endforeach
        </tbody>
    </table>
    @foreach (['description' => 'Description', 'rationale' => 'Rationale'] as $key => $label)
        @if (in_array($key, $enabledFields, true))
            <h2>{{ $label }}</h2><p>{{ $changeControl->{$key} ?: '-' }}</p>
        @endif
    @endforeach
    @if (in_array('document_impacts', $enabledFields, true))
        <h2>Controlled Document Impacts</h2>
        <table><thead><tr><th>Document</th><th>Action</th><th>Rationale</th><th>Result</th></tr></thead><tbody>
        @forelse ($changeControl->documentImpacts as $impact)
            <tr><td>{{ $impact->sourceDocument?->document_number ?? '-' }}</td><td>{{ $impact->required_action->value }}</td><td>{{ $impact->rationale }}</td><td>{{ $impact->resultDocument?->document_number ?? '-' }}</td></tr>
        @empty
            <tr><td colspan="4">No document impacts recorded.</td></tr>
        @endforelse
        </tbody></table>
    @endif
    @if (in_array('milestones', $enabledFields, true))
        <h2>Lifecycle Milestones</h2>
        <table><tbody>
        @foreach (['submitted_at' => 'Submitted', 'approved_at' => 'Approved', 'implementation_due_at' => 'Implementation Due', 'implemented_at' => 'Implemented', 'effectiveness_due_at' => 'Effectiveness Due', 'effectiveness_verified_at' => 'Effectiveness Verified', 'closed_at' => 'Closed'] as $field => $label)
            <tr><th>{{ $label }}</th><td>{{ app(\App\Support\Formatting\DateFormatSettings::class)->formatDateTime($changeControl->{$field}) ?? '-' }}</td></tr>
        @endforeach
        </tbody></table>
    @endif
    @if (in_array('audit_events', $enabledFields, true))
        <h2>Decision & Audit Trail</h2>
        <table><thead><tr><th>Time</th><th>Action</th><th>Actor</th><th>Reason</th></tr></thead><tbody>
        @foreach ($changeControl->auditEvents as $event)
            <tr><td>{{ app(\App\Support\Formatting\DateFormatSettings::class)->formatDateTime($event->occurred_at) }}</td><td>{{ $event->from_status?->value ?? 'created' }} → {{ $event->to_status->value }}</td><td>{{ $event->actor?->name ?? '-' }}</td><td>{{ $event->reason ?? '-' }}</td></tr>
        @endforeach
        </tbody></table>
    @endif
    <p style="color: #64748b; margin-top: 24px;">
        Generated {{ app(\App\Support\Formatting\DateFormatSettings::class)->formatDateTime(now()) }} by {{ auth()->user()->name }} · Template {{ $template->layout_key }}
    </p>
</body>
</html>
