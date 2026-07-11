<x-filament-panels::page>
    @if (filled($this->record->summary))
        <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
            {{ $this->record->summary }}
        </p>
    @endif

    <div @class([
        'bg-white dark:bg-gray-900',
        'p-6',
        'rounded-xl',
        'shadow-sm',
        'ring-1',
        'ring-gray-950/5',
        'dark:ring-white/10',
        'knowledge-guide-content',
    ])>
        {!! $this->record->renderedHtml() !!}
    </div>

    <style>
        .knowledge-guide-content {
            line-height: 1.7;
        }

        .knowledge-guide-content h1 {
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .knowledge-guide-content h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
        }

        .knowledge-guide-content h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .knowledge-guide-content p,
        .knowledge-guide-content ul,
        .knowledge-guide-content ol {
            margin-bottom: 1rem;
        }

        .knowledge-guide-content ul,
        .knowledge-guide-content ol {
            padding-left: 1.5rem;
        }

        .knowledge-guide-content ul {
            list-style-type: disc;
        }

        .knowledge-guide-content ol {
            list-style-type: decimal;
        }

        .knowledge-guide-content li {
            margin-bottom: 0.25rem;
        }

        .knowledge-guide-content table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .knowledge-guide-content th,
        .knowledge-guide-content td {
            border: 1px solid rgb(229 231 235);
            padding: 0.5rem 0.75rem;
            text-align: left;
            vertical-align: top;
        }

        .dark .knowledge-guide-content th,
        .dark .knowledge-guide-content td {
            border-color: rgb(55 65 81);
        }

        .knowledge-guide-content th {
            font-weight: 600;
            background-color: rgb(249 250 251);
        }

        .dark .knowledge-guide-content th {
            background-color: rgb(31 41 55);
        }

        .knowledge-guide-content code {
            font-family: ui-monospace, monospace;
            font-size: 0.875em;
            background-color: rgb(243 244 246);
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
        }

        .dark .knowledge-guide-content code {
            background-color: rgb(31 41 55);
        }

        .knowledge-guide-content hr {
            margin: 2rem 0;
            border-color: rgb(229 231 235);
        }

        .dark .knowledge-guide-content hr {
            border-color: rgb(55 65 81);
        }

        .knowledge-guide-content strong {
            font-weight: 600;
        }
    </style>
</x-filament-panels::page>
