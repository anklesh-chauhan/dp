@php
    $signatureStyle = $fieldConfig['approvals']['signature_style'] ?? \App\Domain\Reporting\Support\PrintApprovalSignatureLayout::STYLE_ELECTRONIC;
    $signatureGroups = app(\App\Domain\Reporting\Support\PrintApprovalSignatureLayout::class)->groups(
        $document,
        $signatureStyle,
        $issuance ?? null,
    );
    $usesManualLines = collect($signatureGroups)->contains(
        fn (array $group): bool => collect($group['entries'])->contains(fn (array $entry): bool => $entry['manual'] ?? false),
    );
@endphp

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
                        @if ($entry['manual'] ?? false)
                            <div class="signature-blank-line" aria-hidden="true"></div>
                        @else
                            @foreach ($entry['signature_lines'] as $line)
                                <div>{{ $line }}</div>
                            @endforeach
                        @endif
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
@if ($usesManualLines)
    <p class="muted signature-manifestation-note">
        Sign and date on the lines above. Printed name and designation identify the expected signer.
    </p>
@else
    <p class="muted signature-manifestation-note">
        Electronic signatures shown above include the signer identity, signature meaning, and signed date/time as attributable GxP records.
    </p>
@endif
