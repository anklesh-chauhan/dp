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
     * DMS knowledge library. Seeded whenever this seeder runs (DMS path).
     *
     * @var list<array{slug: string, file: string, sort_order: int, title?: string, summary?: string}>
     */
    private array $dmsGuides = [
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

    /**
     * QMS knowledge library. Seeded from QmsModuleSeeder when QMS is enabled.
     *
     * @var list<array{slug: string, file: string, sort_order: int, title?: string, summary?: string}>
     */
    private array $qmsGuides = [
        [
            'slug' => 'qms-complaint-handling',
            'file' => 'qms-complaint-handling.md',
            'sort_order' => 101,
            'title' => 'Complaint Handling',
            'summary' => 'Intake, assess, investigate, respond to, and close complaints with optional Deviation and recall handoffs.',
        ],
        [
            'slug' => 'qms-self-inspection',
            'file' => 'qms-self-inspection.md',
            'sort_order' => 102,
            'title' => 'Self-Inspection / Internal Audit',
            'summary' => 'Schedule and close internal audits, disposition findings, and hand off actionable nonconformities to CAPA.',
        ],
        [
            'slug' => 'qms-product-quality-review',
            'file' => 'qms-product-quality-review.md',
            'sort_order' => 103,
            'title' => 'Product Quality Review (PQR)',
            'summary' => 'Complete attributable annual product quality reviews with signed approval and optional CAPA or change follow-up.',
        ],
        [
            'slug' => 'qms-product-recall-and-returns',
            'file' => 'qms-product-recall-and-returns.md',
            'sort_order' => 104,
            'title' => 'Product Recall & Returns',
            'summary' => 'Manage market recalls and product returns with QA disposition, separate from DMS controlled-copy recall.',
        ],
        [
            'slug' => 'qms-laboratory-oos',
            'file' => 'qms-laboratory-oos.md',
            'sort_order' => 105,
            'title' => 'Laboratory OOS / OOT',
            'summary' => 'Record Phase I/II laboratory OOS and OOT events with investigation and Deviation gates before signed closure.',
        ],
        [
            'slug' => 'qms-schedule-m-gap-assessment',
            'file' => 'qms-schedule-m-gap-assessment.md',
            'sort_order' => 106,
            'title' => 'Schedule M Gap Assessment',
            'summary' => 'Run Part I clause gap assessments, maintain Site Master Files, and export entitlement-gated inspector evidence packs.',
        ],
    ];

    public function run(): void
    {
        $this->seedModuleGuides(ProductModule::DMS, $this->dmsGuides);
    }

    public function seedQmsGuides(): void
    {
        $this->seedModuleGuides(ProductModule::QMS, $this->qmsGuides);
    }

    /**
     * @param  list<array{slug: string, file: string, sort_order: int, title?: string, summary?: string}>  $guides
     */
    private function seedModuleGuides(ProductModule $module, array $guides): void
    {
        $seededSlugs = [];

        foreach ($guides as $guide) {
            $path = resource_path('knowledge-guides/'.$guide['file']);

            if (! File::exists($path)) {
                continue;
            }

            $content = File::get($path);
            $seededSlugs[] = $guide['slug'];

            KnowledgeGuide::query()->updateOrCreate(
                ['slug' => $guide['slug']],
                [
                    'title' => $guide['title'] ?? $this->titleFromContent($content, $module),
                    'product_module' => $module,
                    'summary' => $guide['summary'] ?? $this->summaryFromContent($content, $module),
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
            ->where('product_module', $module)
            ->whereNotIn('slug', $seededSlugs)
            ->update(['is_published' => false]);
    }

    private function titleFromContent(string $content, ProductModule $module): string
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches) === 1) {
            return trim($matches[1]);
        }

        return $module === ProductModule::QMS ? 'QMS Guide' : 'DMS Guide';
    }

    private function summaryFromContent(string $content, ProductModule $module): string
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

        return $module === ProductModule::QMS
            ? 'QualiGxP QMS knowledge guide.'
            : 'QualiGxP DMS knowledge guide.';
    }
}
