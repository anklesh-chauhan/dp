<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvExecutionResult;
use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Models\CsvRequirement;
use App\Domain\QMS\Models\CsvRiskAssessment;
use App\Domain\QMS\Models\CsvTestCase;
use App\Domain\QMS\Models\CsvTestExecution;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Services\TabularReportExporter;
use App\Domain\Reporting\Support\PrintLayoutRegistry;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Models\ControlledDocument;
use App\Models\ReportTemplate;
use App\Models\User;
use Database\Seeders\ReportTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('seeds the GMP and ALCOA plus standard template library', function (): void {
    $this->seed(ReportTemplateSeeder::class);

    expect(ReportTemplate::query()->count())->toBe(8)
        ->and(ReportTemplate::query()->where('layout_key', 'sop-gmp-standard')->value('is_system'))->toBeTrue()
        ->and(ReportTemplate::query()->where('scope', ReportScope::DocumentDistribution)->count())->toBe(3)
        ->and(ReportTemplate::query()->where('scope', ReportScope::CsvValidationTraceability)->count())->toBe(2)
        ->and(ReportTemplate::query()->where('layout_key', 'csv-validation-summary-pdf')->value('is_system'))->toBeTrue();
});

it('preserves configured field order and rejects unsupported system fields', function (): void {
    $registry = app(ReportFieldRegistry::class);
    $fields = $registry->defaultFields(ReportScope::DocumentDistribution);
    $reordered = [
        ['key' => 'title', 'enabled' => true],
        ['key' => 'document_number', 'enabled' => false],
    ];

    $normalized = $registry->normalize(ReportScope::DocumentDistribution, $reordered);

    expect(array_slice(array_column($normalized, 'key'), 0, 2))->toBe(['title', 'document_number'])
        ->and($normalized[0]['label'])->toBe('Title')
        ->and($normalized[0]['width'])->toBe('full')
        ->and($normalized[1]['enabled'])->toBeFalse();

    expect(fn () => $registry->normalize(ReportScope::DocumentDistribution, [
        ['key' => 'not_a_system_field', 'enabled' => true],
    ]))->toThrow(ValidationException::class);
});

it('normalizes safe variable-column header and footer configuration with legacy conversion', function (): void {
    $registry = app(PrintLayoutRegistry::class);

    $settings = $registry->normalizePageSettings([
        'orientation' => 'landscape',
        'primary_color' => '#123ABC',
        'margin_left_mm' => 22,
    ]);
    $headerZones = $registry->normalizeZones([
        'gap_mm' => 0,
        'show_borders' => true,
        'rows' => [[
            'key' => 'identity',
            'cells' => [
                ['key' => 'label', 'width' => 30, 'alignment' => 'left', 'vertical_alignment' => 'center', 'items' => [['token' => 'custom_text', 'custom_text' => '  Document No.  ', 'emphasized' => true]]],
                ['key' => 'value', 'width' => 70, 'alignment' => 'left', 'vertical_alignment' => 'center', 'items' => [['token' => 'document_number', 'show_label' => false]]],
            ],
        ]],
    ]);
    $twoColumnZones = $registry->normalizeZones([
        'gap_mm' => 3,
        'show_borders' => false,
        'columns' => [
            [
                'key' => 'brand',
                'width' => 40,
                'alignment' => 'left',
                'vertical_alignment' => 'top',
                'items' => [['token' => 'logo']],
            ],
            [
                'key' => 'identity',
                'width' => 60,
                'alignment' => 'right',
                'vertical_alignment' => 'bottom',
                'items' => [['token' => 'document_number']],
            ],
        ],
    ], footer: true);

    expect($settings)
        ->orientation->toBe('landscape')
        ->primary_color->toBe('#123abc')
        ->margin_left_mm->toBe(22)
        ->and($headerZones['rows'][0]['cells'][0]['items'][0]['custom_text'])->toBe('Document No.')
        ->and($headerZones['rows'][0]['cells'][0]['items'][0]['emphasized'])->toBeTrue()
        ->and($headerZones['rows'][0]['cells'][1]['items'][0]['show_label'])->toBeFalse()
        ->and($headerZones['repeat_every_page'])->toBeTrue()
        ->and($headerZones['reserved_height_mm'])->toBe(32)
        ->and($twoColumnZones['gap_mm'])->toBe(3)
        ->and($twoColumnZones['show_borders'])->toBeFalse()
        ->and(array_column($twoColumnZones['columns'], 'width'))->toBe([40, 60]);

    expect(fn () => $registry->normalizeZones([
        'rows' => [[
            'key' => 'invalid',
            'cells' => [
                ['key' => 'one', 'width' => 70, 'alignment' => 'left', 'vertical_alignment' => 'top', 'items' => []],
                ['key' => 'two', 'width' => 20, 'alignment' => 'right', 'vertical_alignment' => 'bottom', 'items' => []],
            ],
        ]],
    ]))->toThrow(ValidationException::class);

    expect(fn () => $registry->normalizeZones([
        'left' => [['token' => 'unsafe_html']],
    ], footer: true))->toThrow(ValidationException::class);

    expect(fn () => $registry->normalizeZones([
        'columns' => [
            ['key' => 'one', 'width' => 70, 'alignment' => 'left', 'vertical_alignment' => 'top', 'items' => []],
            ['key' => 'two', 'width' => 20, 'alignment' => 'right', 'vertical_alignment' => 'bottom', 'items' => []],
        ],
    ], footer: true))->toThrow(ValidationException::class);

    $legacyTemplate = new ReportTemplate;

    expect($legacyTemplate->printPageSettings()['paper_size'])->toBe('a4')
        ->and($legacyTemplate->printHeaderZones())->toHaveKeys(['gap_mm', 'show_borders', 'rows'])
        ->and($legacyTemplate->printFooterZones())->toHaveKeys(['gap_mm', 'show_borders', 'columns'])
        ->and($legacyTemplate->printHeaderZones()['rows'])->toHaveCount(4);
});

it('exports only enabled columns in the configured CSV order', function (): void {
    $template = ReportTemplate::factory()->create([
        'scope' => ReportScope::DocumentDistribution,
        'format' => ReportFormat::Csv,
        'fields' => [
            ['key' => 'title', 'enabled' => true],
            ['key' => 'document_number', 'enabled' => true],
            ['key' => 'version', 'enabled' => false],
        ],
    ]);

    $document = new ControlledDocument([
        'title' => 'Sanitation Procedure',
        'document_number' => 'SOP-QA-001',
        'version' => 7,
    ]);

    $response = app(TabularReportExporter::class)->download(
        $template,
        collect([$document]),
        'distribution',
    );
    ob_start();
    $response->sendContent();
    $content = (string) ob_get_clean();

    expect($content)->toContain('Title,"Document Number"')
        ->toContain('"Sanitation Procedure",SOP-QA-001')
        ->not->toContain('Version');
});

it('exports ALCOA plus CSV validation traceability in configured CSV and Excel formats', function (): void {
    $requirement = new CsvRequirement([
        'requirement_identifier' => 'URS-001',
        'statement' => 'Audit trail entries remain attributable and contemporaneous.',
        'criticality' => CsvCriticality::High,
        'status' => CsvRequirementStatus::Approved,
        'gxp_relevant' => true,
        'data_integrity_relevant' => true,
    ]);
    $risk = new CsvRiskAssessment(['risk_identifier' => 'RA-001']);
    $testCase = new CsvTestCase(['test_identifier' => 'OQ-014']);
    $execution = new CsvTestExecution([
        'execution_no' => 1,
        'result' => CsvExecutionResult::Passed,
        'evidence_summary' => 'EV-014 audit-trail export',
        'executed_by' => 10,
        'reviewed_by' => 20,
        'reviewed_at' => now(),
    ]);
    $execution->setRelation('testCase', $testCase);
    $testCase->setRelation('executions', collect([$execution]));
    $requirement->setRelation('risks', collect([$risk]));
    $requirement->setRelation('testCases', collect([$testCase]));

    $fields = app(ReportFieldRegistry::class)->defaultFields(ReportScope::CsvValidationTraceability);
    $fields = collect($fields)->map(function (array $field): array {
        $field['enabled'] = in_array($field['key'], [
            'requirement_identifier',
            'test_identifiers',
            'execution_results',
            'evidence_references',
            'independent_review',
        ], true);

        return $field;
    })->all();

    $csvTemplate = ReportTemplate::factory()->create([
        'scope' => ReportScope::CsvValidationTraceability,
        'format' => ReportFormat::Csv,
        'fields' => $fields,
    ]);
    $csvResponse = app(TabularReportExporter::class)->download($csvTemplate, [$requirement], 'traceability');
    ob_start();
    $csvResponse->sendContent();
    $content = (string) ob_get_clean();

    expect($content)
        ->toContain('Requirement ID')
        ->toContain('URS-001')
        ->toContain('OQ-014: Passed')
        ->toContain('EV-014 audit-trail export')
        ->toContain('Complete');

    $excelTemplate = ReportTemplate::factory()->create([
        'scope' => ReportScope::CsvValidationTraceability,
        'format' => ReportFormat::Excel,
        'fields' => $fields,
    ]);
    $excelResponse = app(TabularReportExporter::class)->download($excelTemplate, [$requirement], 'traceability');

    expect($excelResponse->headers->get('content-type'))
        ->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->and($excelResponse->headers->get('content-disposition'))->toContain('traceability.xlsx');
});

it('provides a printable template-driven CSV validation summary', function (): void {
    $fields = app(ReportFieldRegistry::class)->definitions(ReportScope::CsvValidationSummary);
    $view = file_get_contents(resource_path('views/reports/csv-validation-summary.blade.php'));

    expect($fields)
        ->toHaveKeys([
            'project_number',
            'system_identity',
            'gxp_classification',
            'traceability_totals',
            'test_outcomes',
            'release_baseline',
            'audit_events',
        ])
        ->and($view)
        ->toContain('Print / Save PDF')
        ->toContain('Signed Lifecycle Audit Trail')
        ->toContain('ReportFieldRegistry')
        ->toContain('counter(pages)');
});

it('protects direct CSV validation summary exports with permission and QMS entitlement', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    Permission::findOrCreate('View:CsvValidationProject', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('View:CsvValidationProject');
    $project = CsvValidationProject::factory()->create([
        'validation_summary' => 'All critical requirements passed their approved acceptance criteria.',
    ]);
    $this->seed(ReportTemplateSeeder::class);
    $template = ReportTemplate::query()->where('layout_key', 'csv-validation-summary-pdf')->firstOrFail();
    $url = route('csv-validation-projects.report', [
        'csvValidationProject' => $project,
        'template' => $template,
    ]);

    $this->actingAs($user)
        ->get($url)
        ->assertOk()
        ->assertSee('Computerized System Validation Summary')
        ->assertSee($project->project_number);

    $unauthorizedUser = User::factory()->create();
    $this->actingAs($unauthorizedUser)->get($url)->assertForbidden();

    config()->set('modules.enabled', ['dms']);
    $this->actingAs($user)->get($url)->assertNotFound();
});

it('renders page counters in the printed page margin', function (): void {
    $view = file_get_contents(resource_path('views/controlled-documents/print.blade.php'));

    expect($view)
        ->toContain('@bottom-right')
        ->toContain('counter(page)')
        ->toContain('counter(pages)');
});

it('repeats configured headers in the reserved print margin', function (): void {
    $view = file_get_contents(resource_path('views/controlled-documents/print.blade.php'));

    expect($view)
        ->toContain('print-header-repeat')
        ->toContain('position: fixed')
        ->toContain('top: 0')
        ->toContain('reserved_height_mm')
        ->not->toContain("top: -{{ \$configuredHeaderZones['reserved_height_mm'] }}mm");
});
