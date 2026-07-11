<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\KnowledgeGuide;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class KnowledgeGuideSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDocumentClassificationGuide();
    }

    private function seedDocumentClassificationGuide(): void
    {
        $path = base_path('USER_GUIDE.md');

        if (! File::exists($path)) {
            return;
        }

        $content = File::get($path);
        $content = str_replace('$ightarrow$', '→', $content);

        $title = 'Document Categorization & Type Selection';
        $summary = 'Determine when to create a document, which QMS category to select, and which document type to assign.';

        if (preg_match('/^#\s+User Guide:\s*(.+)$/m', $content, $matches)) {
            $title = trim($matches[1]);
        }

        foreach (explode("\n", $content) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '---')) {
                continue;
            }

            if (! str_starts_with($line, '*') && ! str_starts_with($line, '-')) {
                $summary = Str::limit($line, 500);

                break;
            }
        }

        KnowledgeGuide::query()->updateOrCreate(
            ['slug' => 'document-classification'],
            [
                'title' => $title,
                'summary' => $summary,
                'content' => $content,
                'sort_order' => 1,
                'is_published' => true,
            ],
        );
    }
}
