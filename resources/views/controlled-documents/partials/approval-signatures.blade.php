@php($signatureGroups = app(\App\Domain\Reporting\Support\PrintApprovalSignatureLayout::class)->groups($document))

<table class="approval-signatures">
    @forelse ($signatureGroups as $group)
        <tbody class="signature-group">
            <tr>
                <th class="signature-group-heading" colspan="3">{{ $group['heading'] }}</th>
            </tr>
            @foreach ($group['entries'] as $entry)
                <tr>
                    <th class="signature-department" rowspan="3">{{ $entry['department'] }}</th>
                    <th class="signature-label">Sign &amp; Date</th>
                    <td class="signature-value signature-sign">
                        @foreach ($entry['signature_lines'] as $line)
                            <div>{{ $line }}</div>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <th class="signature-label">Name</th>
                    <td class="signature-value">{{ $entry['name'] }}</td>
                </tr>
                <tr>
                    <th class="signature-label">Designation</th>
                    <td class="signature-value">{{ $entry['designation'] }}</td>
                </tr>
            @endforeach
        </tbody>
    @empty
        <tbody>
            <tr>
                <td colspan="3">No approval signatures recorded.</td>
            </tr>
        </tbody>
    @endforelse
</table>
<p class="muted signature-manifestation-note">
    Electronic signatures shown above include the signer identity, signature meaning, and signed date/time as attributable GxP records.
</p>
