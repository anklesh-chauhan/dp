<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="DocuPharma brings controlled documents, quality workflows, and compliant collaboration into one focused workspace for life sciences teams.">

        <title>DocuPharma — Quality, controlled</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#faf8f3] font-sans text-slate-950 antialiased selection:bg-amber-200 selection:text-amber-950">
        <div class="relative isolate overflow-hidden">
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[52rem] bg-[radial-gradient(circle_at_75%_20%,rgba(245,158,11,0.20),transparent_26%),radial-gradient(circle_at_15%_10%,rgba(251,191,36,0.12),transparent_28%),linear-gradient(to_bottom,#ffffff,transparent)]"></div>
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[52rem] bg-[linear-gradient(rgba(15,23,42,0.025)_1px,transparent_1px),linear-gradient(90deg,rgba(15,23,42,0.025)_1px,transparent_1px)] bg-[size:42px_42px] [mask-image:linear-gradient(to_bottom,black,transparent)]"></div>

            <header class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-6 lg:px-8">
                <a href="/" class="group flex items-center gap-3" aria-label="DocuPharma home">
                    <span class="grid size-10 place-items-center rounded-xl bg-amber-600 text-white shadow-lg shadow-amber-900/15 transition-transform group-hover:-rotate-3">
                        <svg viewBox="0 0 24 24" fill="none" class="size-6" aria-hidden="true">
                            <path d="M7 4.75h6.5L17 8.25v11H7v-14Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            <path d="M13 4.75v4h4M9.5 12h5M9.5 15h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            <path d="m16.25 18.25 1.5 1.5 3-3.25" stroke="#FDE68A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="text-xl font-semibold tracking-[-0.035em]">Docu<span class="text-amber-600">Pharma</span></span>
                </a>

                <nav class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex" aria-label="Primary navigation">
                    <a href="#platform" class="transition-colors hover:text-amber-700">Platform</a>
                    <a href="#built-for-quality" class="transition-colors hover:text-amber-700">Why DocuPharma</a>
                    <a href="#security" class="transition-colors hover:text-amber-700">Quality & security</a>
                </nav>

                <a href="{{ url('/admin') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm backdrop-blur transition hover:border-amber-600 hover:text-amber-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
                    Open workspace
                    <svg viewBox="0 0 20 20" fill="none" class="size-4" aria-hidden="true">
                        <path d="M4 10h11m-4-4 4 4-4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </header>

            <main>
                <section class="mx-auto grid max-w-7xl items-center gap-14 px-6 pb-24 pt-16 lg:grid-cols-[0.88fr_1.12fr] lg:px-8 lg:pb-32 lg:pt-24">
                    <div class="max-w-2xl">
                        <div class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50/80 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.14em] text-amber-800">
                            <span class="relative flex size-2">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-amber-500 opacity-60"></span>
                                <span class="relative inline-flex size-2 rounded-full bg-amber-600"></span>
                            </span>
                            Built for life sciences quality
                        </div>

                        <h1 class="mt-7 text-5xl font-semibold leading-[0.98] tracking-[-0.055em] text-slate-950 sm:text-6xl lg:text-7xl">
                            Quality work,<br>
                            <span class="text-amber-600">finally in control.</span>
                        </h1>

                        <p class="mt-7 max-w-xl text-lg leading-8 text-slate-600">
                            Bring documents, quality processes, approvals, and AI-assisted work into one clear system—designed for the rigor of regulated teams.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ url('/admin') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-amber-600 px-6 py-3.5 text-sm font-semibold text-white shadow-xl shadow-amber-900/20 transition hover:-translate-y-0.5 hover:bg-amber-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
                                Enter DocuPharma
                                <svg viewBox="0 0 20 20" fill="none" class="size-4" aria-hidden="true">
                                    <path d="M4 10h11m-4-4 4 4-4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                            <a href="#platform" class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white px-6 py-3.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Explore the platform</a>
                        </div>

                        <div class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-slate-500">
                            <span class="flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full bg-amber-100 text-amber-700">✓</span>Controlled workflows</span>
                            <span class="flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full bg-amber-100 text-amber-700">✓</span>Complete traceability</span>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-3xl lg:mx-0">
                        <div class="absolute -inset-8 -z-10 rounded-[3rem] bg-amber-300/20 blur-3xl"></div>
                        <div class="overflow-hidden rounded-[1.75rem] border border-white/80 bg-slate-950 p-2 shadow-2xl shadow-slate-900/20 ring-1 ring-slate-900/10">
                            <div class="overflow-hidden rounded-[1.35rem] bg-[#f8faf9]">
                                <div class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-3">
                                    <div class="flex items-center gap-4">
                                        <div class="flex gap-1.5"><span class="size-2.5 rounded-full bg-red-400"></span><span class="size-2.5 rounded-full bg-amber-400"></span><span class="size-2.5 rounded-full bg-green-400"></span></div>
                                        <span class="hidden text-[11px] font-medium text-slate-400 sm:block">workspace.docupharma</span>
                                    </div>
                                    <div class="flex items-center gap-2"><span class="size-7 rounded-full bg-amber-100"></span><span class="hidden text-xs font-medium text-slate-600 sm:block">Quality Admin</span></div>
                                </div>

                                <div class="grid min-h-[420px] grid-cols-[68px_1fr] sm:grid-cols-[150px_1fr]">
                                    <aside class="border-r border-slate-200 bg-white p-3 sm:p-4">
                                        <div class="mb-7 flex items-center gap-2">
                                            <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-amber-600 text-[10px] font-bold text-white">DP</span>
                                            <span class="hidden text-xs font-semibold sm:block">DocuPharma</span>
                                        </div>
                                        <div class="grid gap-2 text-[10px] font-medium text-slate-500">
                                            <div class="flex items-center gap-2 rounded-lg bg-amber-50 p-2 text-amber-800"><span class="size-4 rounded bg-amber-200"></span><span class="hidden sm:block">Overview</span></div>
                                            @foreach (['Documents', 'Quality', 'Approvals', 'Reports'] as $item)
                                                <div class="flex items-center gap-2 p-2"><span class="size-4 rounded bg-slate-100"></span><span class="hidden sm:block">{{ $item }}</span></div>
                                            @endforeach
                                        </div>
                                    </aside>

                                    <div class="p-4 sm:p-6">
                                        <div class="flex items-start justify-between gap-4">
                                            <div><p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-amber-700">Quality overview</p><h2 class="mt-1 text-lg font-semibold tracking-tight text-slate-900 sm:text-xl">Good morning, Quality Team</h2></div>
                                            <button type="button" class="rounded-lg bg-amber-600 px-3 py-2 text-[10px] font-semibold text-white">New document</button>
                                        </div>

                                        <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
                                            @foreach ([['128', 'Controlled'], ['14', 'In review'], ['96%', 'On time'], ['8', 'Open CAPAs']] as [$value, $label])
                                                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm"><p class="text-xl font-semibold tracking-tight text-slate-900">{{ $value }}</p><p class="mt-1 text-[9px] text-slate-500">{{ $label }}</p></div>
                                            @endforeach
                                        </div>

                                        <div class="mt-4 grid gap-4 xl:grid-cols-[1.35fr_0.65fr]">
                                            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                                <div class="flex items-center justify-between"><p class="text-xs font-semibold text-slate-800">Approval activity</p><span class="text-[9px] text-slate-400">Last 7 days</span></div>
                                                <div class="mt-6 flex h-28 items-end gap-2">
                                                    @foreach ([38, 54, 42, 68, 58, 82, 72, 92, 78, 100, 86, 96] as $height)
                                                        <span class="flex-1 rounded-t-sm bg-amber-500/80" style="height: {{ $height }}%"></span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="rounded-xl bg-slate-900 p-4 text-white shadow-sm">
                                                <div class="flex items-center justify-between"><p class="text-xs font-semibold">AI assistant</p><span class="size-2 rounded-full bg-amber-400"></span></div>
                                                <p class="mt-5 text-[10px] leading-5 text-slate-300">Three documents are ready for metadata review.</p>
                                                <div class="mt-4 rounded-lg bg-white/10 p-3 text-[9px] text-amber-200">Review suggestions →</div>
                                            </div>
                                        </div>

                                        <div class="mt-4 hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:block">
                                            <div class="flex items-center justify-between"><p class="text-xs font-semibold">Recently updated</p><p class="text-[9px] text-amber-700">View all</p></div>
                                            <div class="mt-3 grid gap-2">
                                                @foreach ([['SOP-042', 'Cleaning validation protocol', 'Approved'], ['WI-118', 'Batch record review', 'In review']] as [$code, $name, $status])
                                                    <div class="grid grid-cols-[55px_1fr_auto] items-center gap-3 border-t border-slate-100 pt-2 text-[9px]"><span class="font-semibold text-slate-500">{{ $code }}</span><span class="text-slate-700">{{ $name }}</span><span class="rounded-full bg-amber-50 px-2 py-1 text-amber-700">{{ $status }}</span></div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -bottom-6 -left-4 hidden items-center gap-3 rounded-2xl border border-white bg-white/95 p-4 shadow-xl backdrop-blur sm:flex">
                            <span class="grid size-10 place-items-center rounded-xl bg-amber-100 text-amber-700">✓</span>
                            <div><p class="text-xs font-semibold text-slate-900">Approval completed</p><p class="mt-0.5 text-[10px] text-slate-500">Audit trail captured</p></div>
                        </div>
                    </div>
                </section>

                <section id="platform" class="border-y border-slate-200/80 bg-white/70 py-24 backdrop-blur sm:py-28">
                    <div class="mx-auto max-w-7xl px-6 lg:px-8">
                        <div class="max-w-2xl">
                            <p class="text-sm font-semibold uppercase tracking-[0.15em] text-amber-700">One connected platform</p>
                            <h2 class="mt-4 text-4xl font-semibold tracking-[-0.045em] text-slate-950 sm:text-5xl">Every quality process.<br>One source of truth.</h2>
                            <p class="mt-5 text-lg leading-8 text-slate-600">Move from scattered files and disconnected follow-ups to controlled, visible work across your quality system.</p>
                        </div>

                        <div class="mt-14 grid gap-5 md:grid-cols-3">
                            @foreach ([
                                ['01', 'Document control', 'Create, review, approve, issue, revise, and retain controlled content with complete version history.', 'DMS'],
                                ['02', 'Quality management', 'Connect deviations, CAPA, change control, audits, risk, suppliers, and management review.', 'QMS'],
                                ['03', 'Responsible AI', 'Accelerate drafting and structured work with module-aware AI assistance and observable execution.', 'AI'],
                            ] as [$number, $title, $copy, $module])
                                <article class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-1 hover:border-amber-200 hover:shadow-xl hover:shadow-amber-900/5">
                                    <div class="flex items-center justify-between"><span class="text-sm font-semibold text-amber-700">{{ $number }}</span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold tracking-wider text-slate-600">{{ $module }}</span></div>
                                    <h3 class="mt-12 text-2xl font-semibold tracking-tight">{{ $title }}</h3>
                                    <p class="mt-4 leading-7 text-slate-600">{{ $copy }}</p>
                                    <div class="mt-8 h-px bg-slate-200 transition-colors group-hover:bg-amber-300"></div>
                                    <p class="mt-5 text-sm font-semibold text-slate-800">Built into the same workspace <span class="text-amber-700">→</span></p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section id="built-for-quality" class="mx-auto grid max-w-7xl gap-14 px-6 py-24 lg:grid-cols-2 lg:items-center lg:px-8 lg:py-32">
                    <div class="relative min-h-[430px] overflow-hidden rounded-[2rem] bg-slate-950 p-7 text-white sm:p-10">
                        <div class="absolute right-0 top-0 size-64 rounded-full bg-amber-500/20 blur-3xl"></div>
                        <p class="relative text-xs font-semibold uppercase tracking-[0.15em] text-amber-300">A clear chain of control</p>
                        <div class="relative mt-10 grid gap-3">
                            @foreach ([['01', 'Draft created', 'Owner assigned'], ['02', 'Review completed', 'Evidence recorded'], ['03', 'Approval signed', 'Identity verified'], ['04', 'Version issued', 'History preserved']] as [$step, $title, $detail])
                                <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-400/15 text-xs font-bold text-amber-300">{{ $step }}</span>
                                    <div class="flex-1"><p class="text-sm font-semibold">{{ $title }}</p><p class="mt-1 text-xs text-slate-400">{{ $detail }}</p></div>
                                    <span class="text-amber-400">✓</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.15em] text-amber-700">Built for quality teams</p>
                        <h2 class="mt-4 text-4xl font-semibold tracking-[-0.045em] sm:text-5xl">Less chasing.<br>More confidence.</h2>
                        <p class="mt-6 text-lg leading-8 text-slate-600">DocuPharma makes ownership, status, evidence, and history visible at every step—so teams can focus on quality instead of administration.</p>
                        <div class="mt-10 grid gap-6 sm:grid-cols-2">
                            @foreach ([['Clear accountability', 'Know who owns the next action and what is holding work back.'], ['Connected evidence', 'Keep decisions, signatures, attachments, and changes with the record.'], ['Purpose-built workflows', 'Apply structured quality processes without forcing teams into spreadsheets.'], ['Modular by design', 'Use DMS as the core and add QMS or AI capabilities when needed.']] as [$title, $copy])
                                <div><span class="mb-3 block size-2 rounded-full bg-amber-600"></span><h3 class="font-semibold text-slate-900">{{ $title }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $copy }}</p></div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section id="security" class="px-6 pb-24 lg:px-8 lg:pb-32">
                    <div class="mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-amber-600 px-6 py-16 text-center text-white shadow-2xl shadow-amber-900/20 sm:px-12 sm:py-20">
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-100">Quality starts with control</p>
                        <h2 class="mx-auto mt-5 max-w-3xl text-4xl font-semibold tracking-[-0.045em] sm:text-5xl">Give every document and decision a trustworthy home.</h2>
                        <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-amber-50">Private file handling, permission-aware actions, attributable approvals, immutable history, and module entitlements are designed into the platform.</p>
                        <a href="{{ url('/admin') }}" class="mt-9 inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3.5 text-sm font-semibold text-amber-800 shadow-lg transition hover:-translate-y-0.5 hover:bg-amber-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Open your workspace <span aria-hidden="true">→</span></a>
                    </div>
                </section>
            </main>

            <footer class="border-t border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-6 py-8 sm:flex-row sm:items-center lg:px-8">
                    <div class="flex items-center gap-3"><span class="grid size-8 place-items-center rounded-lg bg-amber-600 text-xs font-bold text-white">DP</span><span class="font-semibold tracking-tight">DocuPharma</span></div>
                    <p class="text-sm text-slate-500">Purpose-built quality operations for life sciences.</p>
                    <p class="text-xs text-slate-400">© {{ date('Y') }} DocuPharma</p>
                </div>
            </footer>
        </div>
    </body>
</html>
