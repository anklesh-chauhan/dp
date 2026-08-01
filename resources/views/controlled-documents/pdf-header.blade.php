<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @php($headerZones = $reportTemplate->printHeaderZones())
    <style>
        * { box-sizing: border-box; }
        body { color: {{ $reportTemplate->printPageSettings()['primary_color'] }}; font-family: {{ match($reportTemplate->printPageSettings()['font_family']) { 'times' => '"Times New Roman", serif', 'georgia' => 'Georgia, serif', default => 'Arial, Helvetica, sans-serif' } }}; font-size: {{ $reportTemplate->printPageSettings()['font_size'] }}pt; line-height: 1.2; margin: 0; padding: 0 {{ $reportTemplate->printPageSettings()['margin_right_mm'] }}mm 0 {{ $reportTemplate->printPageSettings()['margin_left_mm'] }}mm; width: 100%; }
        .print-table { display: flex; flex-direction: column; width: 100%; }
        .print-table-row { display: grid; }
        .print-table-bordered { border-left: 1px solid currentColor; border-top: 1px solid currentColor; }
        .print-table-bordered .print-zone { border-bottom: 1px solid currentColor; border-right: 1px solid currentColor; }
        .print-zone { display: flex; flex-direction: column; justify-content: center; min-height: 7mm; overflow-wrap: anywhere; padding: 1mm; }
        .print-zone-center { align-items: center; text-align: center; }
        .print-zone-right { align-items: flex-end; text-align: right; }
        .print-zone-emphasized { font-weight: 700; }
        .zone-document-title { font-size: 1em; }
        .organization-logo { max-height: 9mm; max-width: 28mm; object-fit: contain; }
    </style>
</head>
<body>@include('reports.partials.print-header')</body>
</html>
