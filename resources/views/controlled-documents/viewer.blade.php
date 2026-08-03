<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $document->document_number }} - Controlled Viewer</title>
    @vite(['resources/js/pdf-viewer.js'])
    <style>
        :root { --viewer-bg: #f3f4f6; --viewer-surface: #ffffff; --viewer-toolbar: #ffffff; --viewer-border: #e5e7eb; --viewer-text: #111827; --viewer-muted: #6b7280; --viewer-control: #ffffff; --viewer-primary: #2563eb; --viewer-primary-hover: #1d4ed8; --viewer-shadow: rgb(15 23 42 / 0.14); }
        * { box-sizing: border-box; }
        body { background: var(--viewer-bg); color: var(--viewer-text); font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; min-height: 100vh; }
        .viewer-toolbar { align-items: center; background: color-mix(in srgb, var(--viewer-toolbar) 94%, transparent); border-bottom: 1px solid var(--viewer-border); box-shadow: 0 1px 3px rgb(15 23 42 / 0.08); display: flex; gap: 10px; min-height: 64px; padding: 10px 18px; position: sticky; top: 0; z-index: 20; }
        .viewer-title { flex: 1; min-width: 0; }
        .viewer-title strong, .viewer-title span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .viewer-title span { color: var(--viewer-muted); font-size: 12px; margin-top: 3px; }
        .viewer-button { align-items: center; background: var(--viewer-control); border: 1px solid var(--viewer-border); border-radius: 8px; color: var(--viewer-text); cursor: pointer; display: inline-flex; font: inherit; font-size: 14px; font-weight: 600; gap: 6px; min-height: 38px; padding: 8px 12px; text-decoration: none; transition: background .15s ease, border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .viewer-button:hover { background: var(--viewer-bg); border-color: #9ca3af; box-shadow: 0 1px 2px rgb(15 23 42 / 0.08); }
        .viewer-button:focus-visible { outline: 2px solid var(--viewer-primary); outline-offset: 2px; }
        .viewer-button:active { transform: translateY(1px); }
        .viewer-button-primary { background: var(--viewer-primary); border-color: var(--viewer-primary); color: #fff; }
        .viewer-button-primary:hover { background: var(--viewer-primary-hover); border-color: var(--viewer-primary-hover); }
        .viewer-pages { align-items: center; display: flex; flex-direction: column; gap: 22px; padding: 28px 20px 44px; }
        .viewer-page { background: var(--viewer-surface); box-shadow: 0 8px 24px var(--viewer-shadow); max-width: 100%; position: relative; }
        .viewer-page canvas { display: block; height: auto; max-width: 100%; }
        .viewer-watermark { color: #b91c1c; font-size: 14px; font-weight: 700; inset: 0; opacity: .12; overflow: hidden; pointer-events: none; position: absolute; user-select: none; }
        .viewer-watermark span { left: 50%; letter-spacing: .05em; position: absolute; top: 50%; transform: translate(-50%, -50%) rotate(-32deg); white-space: nowrap; }
        .viewer-loading, .viewer-error { background: var(--viewer-surface); border: 1px solid var(--viewer-border); border-radius: 10px; color: var(--viewer-muted); margin: 48px auto; max-width: 620px; padding: 22px; text-align: center; }
        .viewer-error { border-color: #fca5a5; color: #b91c1c; }
        .viewer-status { color: var(--viewer-muted); font-size: 13px; min-width: 80px; text-align: center; }
        @media (prefers-color-scheme: dark) {
            :root { --viewer-bg: #111827; --viewer-surface: #1f2937; --viewer-toolbar: #111827; --viewer-border: #374151; --viewer-text: #f9fafb; --viewer-muted: #9ca3af; --viewer-control: #1f2937; --viewer-shadow: rgb(0 0 0 / 0.35); }
        }
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
