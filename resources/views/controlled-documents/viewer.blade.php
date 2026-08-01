<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $document->document_number }} - Controlled Viewer</title>
    @vite(['resources/js/pdf-viewer.js'])
    <style>
        * { box-sizing: border-box; }
        body { background: #111827; color: #f8fafc; font-family: Arial, Helvetica, sans-serif; margin: 0; min-height: 100vh; }
        .viewer-toolbar { align-items: center; background: #0f172a; border-bottom: 1px solid #334155; display: flex; gap: 10px; min-height: 58px; padding: 8px 16px; position: sticky; top: 0; z-index: 20; }
        .viewer-title { flex: 1; min-width: 0; }
        .viewer-title strong, .viewer-title span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .viewer-title span { color: #94a3b8; font-size: 12px; margin-top: 2px; }
        .viewer-button { align-items: center; background: #1e293b; border: 1px solid #475569; border-radius: 6px; color: #fff; cursor: pointer; display: inline-flex; font: inherit; gap: 6px; min-height: 36px; padding: 7px 11px; text-decoration: none; }
        .viewer-button:hover { background: #334155; }
        .viewer-button-primary { background: #2563eb; border-color: #3b82f6; }
        .viewer-button-primary:hover { background: #1d4ed8; }
        .viewer-pages { align-items: center; display: flex; flex-direction: column; gap: 22px; padding: 24px; }
        .viewer-page { background: #fff; box-shadow: 0 10px 35px #0009; max-width: 100%; position: relative; }
        .viewer-page canvas { display: block; height: auto; max-width: 100%; }
        .viewer-watermark { color: #b91c1c; font-size: 14px; font-weight: 700; inset: 0; opacity: .12; overflow: hidden; pointer-events: none; position: absolute; user-select: none; }
        .viewer-watermark span { left: 50%; letter-spacing: .05em; position: absolute; top: 50%; transform: translate(-50%, -50%) rotate(-32deg); white-space: nowrap; }
        .viewer-loading, .viewer-error { background: #1e293b; border-radius: 8px; margin: 48px auto; max-width: 620px; padding: 22px; text-align: center; }
        .viewer-error { background: #7f1d1d; }
        .viewer-status { color: #cbd5e1; font-size: 13px; min-width: 80px; text-align: center; }
        @media (max-width: 720px) {
            .viewer-toolbar { flex-wrap: wrap; }
            .viewer-title { flex-basis: 100%; }
            .viewer-pages { padding: 12px 4px; }
            .viewer-button { padding: 6px 9px; }
        }
        @media print { body { display: none !important; } }
    </style>
</head>
<body>
    <main
        id="controlled-pdf-viewer"
        data-pdf-url="{{ $contentUrl }}"
        data-watermark="{{ $watermark }}"
    >
        <nav class="viewer-toolbar" aria-label="Controlled PDF toolbar">
            <div class="viewer-title">
                <strong>{{ $document->document_number }} - {{ $document->title }}</strong>
                <span>Controlled viewer - actions are permission-based and audited</span>
            </div>
            <button class="viewer-button" type="button" data-action="zoom-out" aria-label="Zoom out">-</button>
            <span class="viewer-status" data-role="zoom">100%</span>
            <button class="viewer-button" type="button" data-action="zoom-in" aria-label="Zoom in">+</button>
            <span class="viewer-status" data-role="pages">Loading...</span>
            @if ($printUrl)
                <a class="viewer-button viewer-button-primary" href="{{ $printUrl }}" target="_blank" rel="noopener">Print</a>
            @endif
            @if ($downloadUrl)
                <a class="viewer-button" href="{{ $downloadUrl }}">Download</a>
            @endif
        </nav>
        <div class="viewer-loading" data-role="loading">Preparing the controlled document...</div>
        <div class="viewer-pages" data-role="pages-container"></div>
    </main>
</body>
</html>
