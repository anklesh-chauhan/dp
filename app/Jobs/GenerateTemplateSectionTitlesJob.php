<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DocumentTemplateVersion;
use App\Services\AI\Contracts\TemplateGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateTemplateSectionTitlesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public DocumentTemplateVersion $version) {}

    public function handle(TemplateGenerator $generator): void
    {
        if (! $this->version->isContentEditable()) {
            return;
        }

        $template = $this->version->template()->with(['category', 'documentType'])->firstOrFail();
        $existingSections = $this->version->sections()->orderBy('section_order')->get();
        $titles = $generator->generateSectionTitles($template->toArray(), max($existingSections->count(), 8));

        if ($titles === null) {
            return;
        }

        if ($existingSections->isEmpty()) {
            foreach ($titles as $index => $title) {
                $this->version->sections()->create([
                    'title' => trim($title),
                    'section_order' => $index + 1,
                    'section_type' => 'rich_text',
                    'content' => null,
                    'is_required' => true,
                ]);
            }

            return;
        }

        foreach ($existingSections as $index => $section) {
            if (isset($titles[$index])) {
                $section->update(['title' => trim($titles[$index])]);
            }
        }
    }
}
