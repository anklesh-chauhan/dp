<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->document_number }} - {{ $document->title }}</title>
    <style>
        :root {
            color: #1f2933;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.5;
        }

        body {
            background: #eef2f6;
            margin: 0;
            padding: 24px;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            margin: 0 auto 16px;
            max-width: 900px;
        }

        button {
            background: #1f2937;
            border: 0;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            font: inherit;
            padding: 9px 14px;
        }

        .page {
            background: #fff;
            box-shadow: 0 18px 48px rgba(15, 23, 42, .16);
            margin: 0 auto;
            max-width: 900px;
            min-height: 1120px;
            padding: 48px;
        }

        header {
            border-bottom: 2px solid #111827;
            margin-bottom: 24px;
            padding-bottom: 18px;
        }

        h1 {
            font-size: 24px;
            margin: 0 0 8px;
        }

        h2 {
            border-bottom: 1px solid #d5dbe3;
            font-size: 16px;
            margin: 28px 0 12px;
            padding-bottom: 6px;
        }

        h3 {
            font-size: 14px;
            margin: 18px 0 8px;
        }

        table {
            border-collapse: collapse;
            margin: 0 0 14px;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #d5dbe3;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f5f7fa;
            font-weight: 700;
            width: 28%;
        }

        .meta {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .section {
            break-inside: avoid;
            margin-top: 18px;
        }

        .content {
            border: 1px solid #d5dbe3;
            padding: 12px 14px;
        }

        .content :first-child {
            margin-top: 0;
        }

        .content :last-child {
            margin-bottom: 0;
        }

        .muted {
            color: #617182;
        }

        .watermark {
            border: 2px dashed #b45309;
            color: #92400e;
            font-weight: 700;
            letter-spacing: .08em;
            margin: 0 0 18px;
            padding: 10px 14px;
            text-align: center;
            text-transform: uppercase;
        }

        .reference-box {
            background: #f8fafc;
            border: 1px solid #d5dbe3;
            margin-bottom: 18px;
            padding: 12px 14px;
        }

        @page {
            margin: 18mm;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .page {
                box-shadow: none;
                margin: 0;
                max-width: none;
                min-height: auto;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

    <main class="page">
        @if ($issuance)
            <div class="watermark">
                Controlled Copy {{ $issuance->copy_number }} | {{ $issuance->watermark_code }} | Issued {{ $issuance->issued_at->toDayDateTimeString() }}
            </div>
        @endif

        <header>
            <h1>{{ $document->title }}</h1>
            <div class="muted">{{ $document->document_number }} | Version {{ $document->version }} | {{ $document->documentStatus->name }}</div>
        </header>

        @if ($document->referenced_sop_number)
            <section class="reference-box">
                <strong>Referenced SOP:</strong>
                {{ $document->referenced_sop_number }}
                v{{ $document->referenced_sop_version }}
                @if ($document->referenced_sop_effective_date)
                    (Effective {{ $document->referenced_sop_effective_date->toFormattedDateString() }})
                @endif
            </section>
        @endif

        <section class="meta">
            <table>
                <tbody>
                    <tr>
                        <th>Department</th>
                        <td>{{ $document->department?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Template</th>
                        <td>{{ $document->template?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Owner</th>
                        <td>{{ $document->owner?->name ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <table>
                <tbody>
                    <tr>
                        <th>Effective Date</th>
                        <td>{{ $document->effective_date?->toFormattedDateString() ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Review Date</th>
                        <td>{{ $document->review_date?->toFormattedDateString() ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Prepared By</th>
                        <td>{{ $document->creator?->name ?? '-' }}</td>
                    </tr>
                    @if ($document->batch_number)
                        <tr>
                            <th>Batch Number</th>
                            <td>{{ $document->batch_number }}</td>
                        </tr>
                    @endif
                    @if ($document->product_name)
                        <tr>
                            <th>Product</th>
                            <td>{{ $document->product_name }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </section>

        @if ($document->variables->isNotEmpty())
            <h2>Variables</h2>
            <table>
                <tbody>
                    @foreach ($document->variables as $variable)
                        <tr>
                            <th>{{ str($variable->variable_name)->replace('_', ' ')->title() }}</th>
                            <td>{{ filled($variable->value) ? $variable->value : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h2>Sections</h2>
        @forelse ($document->sections as $section)
            <article class="section">
                <h3>{{ $section->section_order }}. {{ $section->title }}</h3>
                <div class="content">
                    {!! filled($section->content) ? $section->content : '<p>-</p>' !!}
                </div>
            </article>
        @empty
            <p class="muted">No sections have been added to this SOP document.</p>
        @endforelse

        @if ($document->approvals->isNotEmpty())
            <h2>Approvals</h2>
            <table>
                <thead>
                    <tr>
                        <th>Step</th>
                        <th>Decision</th>
                        <th>Approver</th>
                        <th>Approved At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($document->approvals as $approval)
                        <tr>
                            <td>{{ $approval->workflowStep?->approvalStepType->name  ?? '-' }}</td>
                            <td>{{ $approval->approvalDecision?->name ?? '-' }}</td>
                            <td>{{ $approval->approver?->name ?? '-' }}</td>
                            <td>{{ $approval->approved_at?->toDayDateTimeString() ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </main>
</body>
</html>
