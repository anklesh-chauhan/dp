<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DocumentTemplateSection;
use App\Services\AI\Contracts\TemplateGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompleteTemplateSectionWithAiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public DocumentTemplateSection $section) {}

    public function handle(TemplateGenerator $generator): void
    {
        $section = $this->section->load('templateVersion.template');
        $template = $section->templateVersion->template->load(['category', 'documentType']);
        $content = $generator->completeSection($template->toArray(), $section->title);

        if ($content !== null) {
            $section->update(['content' => $content]);
        }
    }
}
