<section class="title-page" style="{{ $titlePage['page_break_after'] ? 'break-after: page;' : '' }}">
    @if ($titlePage['show_logo'] && filled($organization['logo_data_uri'] ?? null))
        <img class="title-page-logo" src="{{ $organization['logo_data_uri'] }}" alt="{{ $organization['legal_name'] ?? 'Organization' }} logo">
    @endif

    @if ($titlePage['show_organization'])
        <div class="title-page-organization">{{ $organization['legal_name'] ?? '-' }}</div>
    @endif

    <h1>{{ $document->title }}</h1>

    @if (filled($titlePage['subtitle']))
        <div class="title-page-subtitle">{{ $titlePage['subtitle'] }}</div>
    @endif

    @if ($titlePage['show_identity'])
        <div class="title-page-identity">
            <span>{{ $document->document_number }}</span>
            <span>Version {{ $document->version }}</span>
            @if ($document->department)
                <span>{{ $document->department->name }}</span>
            @endif
            @if ($document->effective_date)
                <span>Effective {{ $document->effective_date->toFormattedDateString() }}</span>
            @endif
        </div>
    @endif

    @if ($titlePage['show_controlled_notice'])
        <div class="title-page-notice">{{ $issuance ? 'CONTROLLED COPY' : 'UNCONTROLLED WHEN PRINTED' }}</div>
    @endif
</section>
