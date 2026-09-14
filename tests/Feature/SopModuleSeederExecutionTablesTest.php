<?php

declare(strict_types=1);

use App\Data\ControlledDocumentData;
use App\Domain\DMS\Actions\CreateDocumentFromTemplateAction;
use App\Domain\Reporting\Support\PrintApprovalSignatureLayout;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\Organization;
use App\Models\User;
use App\Models\VariableDataType;
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
        ->and(DocumentTemplate::query()->whereIn('code', [
            'TPL-STRUCTURED-GMP-MANUAL',
            'TPL-CONTROLLED-FORM-GMP-MANUAL',
            'TPL-BMR-BPR-GMP-MANUAL',
            'TPL-CHECKLIST-GMP-MANUAL',
            'TPL-ANNEXURE-GMP-MANUAL',
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
        'TPL-SOP-GMP-MANUAL' => 'sop-gmp-standard-manual',
        'TPL-LOG-GMP-MANUAL' => 'repeating-log-gmp-print-manual',
        'TPL-STRUCTURED-GMP-MANUAL' => 'structured-table-gmp-print-manual',
        'TPL-CONTROLLED-FORM-GMP-MANUAL' => 'controlled-form-gmp-print-manual',
        'TPL-BMR-BPR-GMP-MANUAL' => 'batch-record-gmp-print-manual',
        'TPL-CHECKLIST-GMP-MANUAL' => 'checklist-gmp-print-manual',
        'TPL-ANNEXURE-GMP-MANUAL' => 'annexure-gmp-print-manual',
    ];

    foreach ($mappings as $templateCode => $layoutKey) {
        $template = DocumentTemplate::query()
            ->where('code', $templateCode)
            ->with('reportTemplate')
            ->firstOrFail();

        $approvals = collect($template->reportTemplate?->fields)->firstWhere('key', 'approvals');

        expect($template->report_template_id)->not->toBeNull()
            ->and($template->reportTemplate?->layout_key)->toBe($layoutKey)
            ->and($approvals['signature_style'] ?? null)->toBe(
                str_ends_with($templateCode, '-MANUAL')
                    ? PrintApprovalSignatureLayout::STYLE_MANUAL
                    : PrintApprovalSignatureLayout::STYLE_ELECTRONIC,
            );
    }
});

it('does not seed effective date or review date as template variables', function (): void {
    $this->seed(SopModuleSeeder::class);

    $sopVersion = DocumentTemplate::query()
        ->where('code', 'TPL-SOP-GMP')
        ->with('versions.variables')
        ->sole()
        ->versions
        ->sole();

    $sopVersion->variables()->create([
        'name' => 'effective_date',
        'label' => 'Effective Date',
        'variable_data_type_id' => VariableDataType::idFor(VariableDataType::DATE),
        'required' => false,
    ]);
    $sopVersion->variables()->create([
        'name' => 'review_date',
        'label' => 'Review Date',
        'variable_data_type_id' => VariableDataType::idFor(VariableDataType::DATE),
        'required' => false,
    ]);

    $this->seed(SopModuleSeeder::class);

    $variableNames = DocumentTemplate::query()
        ->whereIn('code', ['TPL-SOP-GMP', 'TPL-SOP-GMP-MANUAL'])
        ->with('versions.variables')
        ->get()
        ->flatMap(fn (DocumentTemplate $template) => $template->versions->flatMap(
            fn (DocumentTemplateVersion $version) => $version->variables->pluck('name'),
        ));

    expect($variableNames)->not->toContain('effective_date', 'review_date');
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
