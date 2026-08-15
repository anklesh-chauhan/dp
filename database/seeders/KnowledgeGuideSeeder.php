<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProductModule;
use App\Models\KnowledgeGuide;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class KnowledgeGuideSeeder extends Seeder
{
    /**
     * DMS-only knowledge library. QMS/AI guides are intentionally omitted until
     * those modules ship published content for this installation.
     *
     * @var list<array{slug: string, file: string, sort_order: int, title?: string, summary?: string}>
     */
    private array $guides = [
        [
            'slug' => 'dms-overview',
            'file' => 'dms-overview.md',
            'sort_order' => 1,
            'title' => 'Getting Started with DMS',
            'summary' => 'DMS navigation, roles, and the end-to-end path from template to controlled copy and retention.',
        ],
        [
            'slug' => KnowledgeGuide::ClassificationSlug,
            'file' => 'document-classification.md',
            'sort_order' => 2,
            'title' => 'Document Types & Classification',
            'summary' => 'Choose the correct DMS document type and format profile for templates and controlled documents.',
        ],
        [
            'slug' => 'templates-and-approvals',
            'file' => 'templates-and-approvals.md',
            'sort_order' => 3,
            'title' => 'Templates & Approvals',
            'summary' => 'Publish document templates and complete electronic approval workflows with separation of duties.',
        ],
        [
            'slug' => 'controlled-documents-lifecycle',
            'file' => 'controlled-documents-lifecycle.md',
            'sort_order' => 4,
            'title' => 'Controlled Documents Lifecycle',
            'summary' => 'Author, submit, revise, obsolete, archive, complete retention, and destroy controlled-document masters.',
        ],
        [
            'slug' => 'issuance-and-execution',
            'file' => 'issuance-and-execution.md',
            'sort_order' => 5,
            'title' => 'Issuance, Printing & Execution',
            'summary' => 'Issue controlled copies, print with issuance numbers, and complete writable GMP execution records.',
        ],
    ];

    public function run(): void
    {
        $seededSlugs = [];

        foreach ($this->guides as $guide) {
            $path = resource_path('knowledge-guides/'.$guide['file']);

            if (! File::exists($path)) {
                continue;
            }

            $content = File::get($path);
            $seededSlugs[] = $guide['slug'];

            KnowledgeGuide::query()->updateOrCreate(
                ['slug' => $guide['slug']],
                [
                    'title' => $guide['title'] ?? $this->titleFromContent($content),
                    'product_module' => ProductModule::DMS,
                    'summary' => $guide['summary'] ?? $this->summaryFromContent($content),
                    'content' => $content,
                    'sort_order' => $guide['sort_order'],
                    'is_published' => true,
                ],
            );
        }

        if ($seededSlugs === []) {
            return;
        }

        KnowledgeGuide::query()
            ->where('product_module', ProductModule::DMS)
            ->whereNotIn('slug', $seededSlugs)
            ->update(['is_published' => false]);
    }

    private function titleFromContent(string $content): string
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches) === 1) {
            return trim($matches[1]);
        }

        return 'DMS Guide';
    }

    private function summaryFromContent(string $content): string
    {
        foreach (explode("\n", $content) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '---') || str_starts_with($line, '|')) {
                continue;
            }

            if (str_starts_with($line, '*') || str_starts_with($line, '-')) {
                continue;
            }

            return Str::limit($line, 500);
        }

        return 'QualiGxP DMS knowledge guide.';
    }
}
