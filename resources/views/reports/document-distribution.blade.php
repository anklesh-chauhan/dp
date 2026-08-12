<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $template->name }}</title>
    <style>
        body { color: #172033; font: 12px/1.4 Arial, sans-serif; margin: 20px; }
        .toolbar { text-align: right; } button { background: #172033; border: 0; color: white; padding: 9px 14px; }
        h1 { margin-bottom: 4px; } .meta { color: #64748b; margin-bottom: 18px; }
        table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #94a3b8; padding: 6px; text-align: left; }
        th { background: #e2e8f0; }
        @media print { .toolbar { display: none; } body { margin: 0; } @page { size: landscape; margin: 12mm; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print / Save PDF</button></div>
    <h1>Document Distribution Sheet</h1>
    <div class="meta">Generated {{ app(\App\Support\Formatting\DateFormatSettings::class)->formatDateTime(now()) }} by {{ auth()->user()->name }}</div>
    <table>
        <thead><tr>
        @foreach ($template->fields as $field)
            @if ($field['enabled'])<th>{{ $field['label'] }}</th>@endif
        @endforeach
        </tr></thead>
        <tbody>
        @foreach ($documents as $document)
            <tr>
            @foreach ($template->fields as $field)
                @if ($field['enabled'])<td>{{ app(\App\Domain\Reporting\Support\ReportFieldRegistry::class)->value($document, $field['key']) }}</td>@endif
            @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
