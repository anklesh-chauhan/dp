<?php

declare(strict_types=1);

use App\Data\ControlledDocumentData;
use App\Domain\DMS\Actions\CreateDocumentFromTemplateAction;
use App\Models\DocumentTemplate;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\ReportTemplateSeeder;
use Database\Seeders\SopModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds reusable GMP execution table definitions idempotently', function (): void {
    $this->seed(SopModuleSeeder::class);
    $this->seed(SopModuleSeeder::class);

    $batchTemplate = DocumentTemplate::query()
        ->where('code', 'TPL-BMR-BPR-GMP')
        ->with('versions.sections')
        ->sole();
    $materialsSection = $batchTemplate->versions->sole()->sections
        ->firstWhere('title', 'Materials and Reconciliation');
    $controlledForm = DocumentTemplate::query()
        ->where('code', 'TPL-CONTROLLED-FORM-GMP')
        ->with('versions.sections')
        ->sole();
    $checklist = DocumentTemplate::query()
        ->where('code', 'TPL-CHECKLIST-GMP')
        ->with('versions.sections')
        ->sole();
    $annexure = DocumentTemplate::query()
        ->where('code', 'TPL-ANNEXURE-GMP')
        ->with('versions.sections')
        ->sole();

    expect(DocumentTemplate::query()->whereIn('code', [
        'TPL-STRUCTURED-GMP',
        'TPL-CONTROLLED-FORM-GMP',
        'TPL-BMR-BPR-GMP',
        'TPL-CHECKLIST-GMP',
        'TPL-ANNEXURE-GMP',
    ])->count())->toBe(5)
        ->and($batchTemplate->versions)->toHaveCount(1)
        ->and($materialsSection->configuration['execution_tables'])->toHaveCount(2)
        ->and($materialsSection->configuration['execution_tables'][0]['fields'])->toContainEqual([
            'label' => 'Material name / code',
            'item_order' => 1,
            'value_type' => 'text',
            'is_required' => true,
        ])
        ->and($materialsSection->configuration['execution_tables'][1]['fields'])->toContainEqual([
            'label' => 'Used quantity',
            'item_order' => 3,
            'value_type' => 'numeric',
            'is_required' => true,
        ])
        ->and($controlledForm->versions->sole()->sections->firstWhere('title', 'Record Identification')->configuration['execution_tables'][0]['execution_layout'])->toBe('field_value')
        ->and(collect($checklist->versions->sole()->sections->firstWhere('title', 'Checks')->configuration['execution_tables'][0]['fields'])->pluck('label'))->toContain('Pass / Fail / N/A')
        ->and(collect($annexure->versions->sole()->sections->firstWhere('title', 'Evidence Package Index')->configuration['execution_tables'][0]['fields'])->pluck('label'))->toContain('Annexure number', 'Integrity / review status');
});

it('links seeded document templates to their GMP print and report templates', function (): void {
    $this->seed(ReportTemplateSeeder::class);
    $this->seed(SopModuleSeeder::class);

    $mappings = [
        'TPL-SOP-GMP' => 'sop-gmp-standard',
        'TPL-LOG-GMP' => 'repeating-log-gmp-print',
        'TPL-STRUCTURED-GMP' => 'structured-table-gmp-print',
        'TPL-CONTROLLED-FORM-GMP' => 'controlled-form-gmp-print',
        'TPL-BMR-BPR-GMP' => 'batch-record-gmp-print',
        'TPL-CHECKLIST-GMP' => 'checklist-gmp-print',
        'TPL-ANNEXURE-GMP' => 'annexure-gmp-print',
    ];

    foreach ($mappings as $templateCode => $layoutKey) {
        $template = DocumentTemplate::query()
            ->where('code', $templateCode)
            ->with('reportTemplate')
            ->firstOrFail();

        expect($template->report_template_id)->not->toBeNull()
            ->and($template->reportTemplate?->layout_key)->toBe($layoutKey);
    }
});

it('creates related execution tables and field headers from a seeded GMP template', function (): void {
    $this->seed(SopModuleSeeder::class);
    Organization::factory()->create();

    $template = DocumentTemplate::query()->where('code', 'TPL-BMR-BPR-GMP')->sole();
    $template->documentType()->update(['requires_sop_reference' => false]);
    $user = User::factory()->create();

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new ControlledDocumentData(
        templateId: $template->id,
        title: 'Example Batch Record',
        ownerId: $user->id,
        createdBy: $user->id,
        documentNumber: 'BMR-QA-00001',
    ));

    $materialsSection = $document->sections->firstWhere('title', 'Materials and Reconciliation');

    expect($materialsSection->executionTables)->toHaveCount(2)
        ->and($materialsSection->executionTables->pluck('title')->all())->toBe([
            'Raw and packaging materials',
            'Material reconciliation',
        ])
        ->and($materialsSection->executionTables->first()->items->pluck('label'))->toContain('Material name / code', 'Issued quantity')
        ->and($materialsSection->executionTables->last()->items->pluck('label'))->toContain('Used quantity', 'Reconciliation status');
});
