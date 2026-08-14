<table class="change-history">
    <thead>
        <tr>
            <th>Version</th>
            <th>Status</th>
            <th>Effective Date</th>
            <th>Description of Change</th>
            <th>Prepared By</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($document->printableChangeHistory() as $version)
            <tr>
                <td>{{ $version->version }}</td>
                <td>{{ $version->documentStatus?->name ?? '-' }}</td>
                <td>{{ app(\App\Support\Formatting\DateFormatSettings::class)->formatDate($version->effective_date) ?? '-' }}</td>
                <td>{{ $version->changeDescription() }}</td>
                <td>{{ $version->creator?->name ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No change history has been recorded for this document series.</td>
            </tr>
        @endforelse
    </tbody>
</table>
