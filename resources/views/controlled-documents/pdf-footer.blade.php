<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @php($footerZones = $reportTemplate->printFooterZones())
    <style>
        * { box-sizing: border-box; }
        body { color: {{ $reportTemplate->printPageSettings()['primary_color'] }}; font-family: {{ match($reportTemplate->printPageSettings()['font_family']) { 'times' => '"Times New Roman", serif', 'georgia' => 'Georgia, serif', default => 'Arial, Helvetica, sans-serif' } }}; font-size: {{ $reportTemplate->printPageSettings()['font_size'] }}pt; line-height: 1.2; margin: 0; padding: 0 {{ $reportTemplate->printPageSettings()['margin_right_mm'] }}mm 0 {{ $reportTemplate->printPageSettings()['margin_left_mm'] }}mm; width: 100%; }
        .print-grid { display: grid; width: 100%; }
        .print-grid-bordered { border: 1px solid currentColor; }
        .print-grid-bordered .print-zone + .print-zone { border-left: 1px solid currentColor; }
        .print-zone { display: flex; flex-direction: column; justify-content: center; min-height: 7mm; overflow-wrap: anywhere; padding: 1mm; }
        .print-zone-center { align-items: center; text-align: center; }
        .print-zone-right { align-items: flex-end; text-align: right; }
        .print-zone-emphasized { font-weight: 700; }
    </style>
</head>
<body>@include('reports.partials.print-footer')</body>
</html>
