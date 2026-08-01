@props([
    'items',
    'alignment' => 'left',
    'verticalAlignment' => 'center',
    'document' => null,
    'organization' => [],
    'issuance' => null,
    'preview' => false,
    'serverPdf' => false,
])

<div class="print-zone print-zone-{{ $alignment }} print-zone-vertical-{{ $verticalAlignment }}">
    @foreach ($items as $item)
        <div class="{{ ($item['emphasized'] ?? false) ? 'print-zone-emphasized' : '' }}">
        @switch($item['token'])
            @case('logo')
                @if ($preview)
                    <div class="sample-logo">LOGO</div>
                @elseif (filled($organization['logo_data_uri'] ?? null) || filled($organization['logo_path'] ?? null))
                    <img
                        class="organization-logo"
                        src="{{ $organization['logo_data_uri'] ?? \Illuminate\Support\Facades\Storage::disk('public')->url($organization['logo_path']) }}"
                        alt="{{ $organization['legal_name'] ?? 'Organization' }} logo"
                    >
                @endif
                @break
            @case('organization_name')
                <strong>{{ $preview ? 'Acme Pharmaceuticals Ltd.' : ($organization['legal_name'] ?? '-') }}</strong>
                @break
            @case('organization_address')
                <span>{{ $preview ? 'Validated Manufacturing Site, India' : collect([
                    $organization['address_line_1'] ?? null,
                    $organization['address_line_2'] ?? null,
                    $organization['city'] ?? null,
                    $organization['state'] ?? null,
                    $organization['postal_code'] ?? null,
                    $organization['country_code'] ?? null,
                ])->filter()->implode(', ') }}</span>
                @break
            @case('registration_number')
                <span>Registration: {{ $preview ? 'GMP-000123' : ($organization['registration_number'] ?? '-') }}</span>
                @break
            @case('document_title')
                <strong class="zone-document-title">{{ $preview ? 'Standard Operating Procedure' : $document?->title }}</strong>
                @break
            @case('document_number')
                <span>{{ ($item['show_label'] ?? true) ? $item['label'].': ' : '' }}{{ $preview ? 'SOP-QA-001' : $document?->document_number }}</span>
                @break
            @case('document_version')
                <span>{{ ($item['show_label'] ?? true) ? $item['label'].': ' : '' }}{{ $preview ? '3' : $document?->version }}</span>
                @break
            @case('document_status')
                <span>{{ ($item['show_label'] ?? true) ? $item['label'].': ' : '' }}{{ $preview ? 'Effective' : $document?->documentStatus?->name }}</span>
                @break
            @case('department')
                <span>{{ ($item['show_label'] ?? true) ? $item['label'].': ' : '' }}{{ $preview ? 'Quality Assurance' : ($document?->department?->name ?? '-') }}</span>
                @break
            @case('effective_date')
                <span>{{ $item['label'] }}: {{ $preview ? now()->toFormattedDateString() : ($document?->effective_date?->toFormattedDateString() ?? '-') }}</span>
                @break
            @case('review_date')
                <span>{{ $item['label'] }}: {{ $preview ? now()->addYear()->toFormattedDateString() : ($document?->review_date?->toFormattedDateString() ?? '-') }}</span>
                @break
            @case('copy_status')
                <strong>{{ $preview ? 'CONTROLLED COPY' : ($issuance ? "CONTROLLED COPY {$issuance->copy_number}" : 'UNCONTROLLED WHEN PRINTED') }}</strong>
                @break
            @case('printed_by')
                <span>{{ $item['label'] }}: {{ $preview ? 'Preview User' : auth()->user()->name }}</span>
                @break
            @case('printed_at')
                <span>{{ $item['label'] }}: {{ now()->toDayDateTimeString() }}</span>
                @break
            @case('template_reference')
                <span>{{ $item['label'] }}: {{ $reportTemplate->layout_key }}</span>
                @break
            @case('controlled_notice')
                <strong>{{ $issuance || $preview ? 'CONTROLLED COPY' : 'UNCONTROLLED WHEN PRINTED' }}</strong>
                @break
            @case('page_number')
                @if ($serverPdf)
                    <span class="page-number">{{ ($item['show_label'] ?? true) ? $item['label'].': ' : '' }}Page @pageNumber of @totalPages</span>
                @elseif ($preview)
                    <span class="page-number">{{ ($item['show_label'] ?? true) ? $item['label'].': ' : '' }}Page 1 of 1</span>
                @endif
                @break
            @case('custom_text')
                <span>{{ $item['custom_text'] }}</span>
                @break
        @endswitch
        </div>
    @endforeach
</div>
