<section class="table-of-contents" style="{{ $toc['page_break_before'] ? 'break-before: page;' : '' }} {{ $toc['page_break_after'] ? 'break-after: page;' : '' }}">
    <h2>{{ $toc['title'] }}</h2>
    @foreach ($document->sections as $section)
        @if ($section->include_in_toc && $section->heading_level <= $toc['max_level'])
            <div class="toc-entry level-{{ $section->heading_level }}">
                <a href="#section-{{ $section->getKey() }}">
                    {{ $toc['show_section_numbers'] ? $section->section_order.'. ' : '' }}{{ $section->toc_title ?: $section->title }}
                </a>
            </div>
        @endif
    @endforeach
</section>
