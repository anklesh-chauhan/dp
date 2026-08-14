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

    expect(ReportTemplate::query()->count())->toBe(15)
        ->and(ReportTemplate::query()->where('layout_key', 'sop-gmp-standard')->value('is_system'))->toBeTrue()
        ->and(ReportTemplate::query()->where('scope', ReportScope::DocumentDistribution)->count())->toBe(3)
        ->and(ReportTemplate::query()->where('scope', ReportScope::CsvValidationTraceability)->count())->toBe(2)
        ->and(ReportTemplate::query()->where('layout_key', 'csv-validation-summary-pdf')->value('is_system'))->toBeTrue();
});

it('seeds GMP controlled document templates with UI body block defaults and a reports and manuals layout', function (): void {
    $this->seed(ReportTemplateSeeder::class);

    $gmpTemplate = ReportTemplate::query()->where('layout_key', 'sop-gmp-standard')->firstOrFail();
    $reportsManualsTemplate = ReportTemplate::query()->where('layout_key', 'reports-manuals-gmp-print')->firstOrFail();

    $enabledKeys = collect($gmpTemplate->fields)->filter(fn (array $field): bool => $field['enabled'])->pluck('key')->all();
    $sections = collect($gmpTemplate->fields)->firstWhere('key', 'sections');
    $approvals = collect($gmpTemplate->fields)->firstWhere('key', 'approvals');

    expect($enabledKeys)->toBe(['approvals', 'sections', 'change_history'])
        ->and($sections)
        ->enabled->toBeTrue()
        ->show_label->toBeFalse()
        ->show_section_titles->toBeTrue()
        ->and($approvals)
        ->enabled->toBeTrue()
        ->show_label->toBeTrue()
        ->and(collect($gmpTemplate->fields)->firstWhere('key', 'organization')['enabled'])->toBeFalse()
        ->and(ReportTemplate::query()->where('layout_key', 'like', '%-gmp-%')->orWhere('layout_key', 'sop-gmp-standard')->count())->toBe(8)
        ->and($reportsManualsTemplate->tocConfiguration())
        ->enabled->toBeTrue()
        ->title->toBe('Table of Contents')
        ->and($reportsManualsTemplate->titlePageConfiguration())
        ->enabled->toBeTrue()
        ->show_logo->toBeTrue()
        ->page_break_after->toBeTrue();
});

it('prints approvals after the title page and table of contents', function (): void {
    $print = file_get_contents(resource_path('views/controlled-documents/print.blade.php'));
    $titlePos = strpos($print, 'controlled-documents.partials.title-page');
    $tocAfterIdentityPos = strpos($print, "toc['position'] === 'after_identity'");
    $tocBeforeSectionsPos = strpos($print, "toc['position'] === 'before_sections'");
    $approvalsPos = strpos($print, "in_array('approvals', \$enabledFields, true)");
    $sectionsPos = strpos($print, "in_array('sections', \$enabledFields, true)");
    $changeHistoryPos = strpos($print, "in_array('change_history', \$enabledFields, true)");

    expect($titlePos)->toBeInt()
        ->and($tocAfterIdentityPos)->toBeInt()
        ->and($tocBeforeSectionsPos)->toBeInt()
        ->and($approvalsPos)->toBeInt()
        ->and($sectionsPos)->toBeInt()
        ->and($changeHistoryPos)->toBeInt()
        ->and($titlePos)->toBeLessThan($tocAfterIdentityPos)
        ->and($tocAfterIdentityPos)->toBeLessThan($tocBeforeSectionsPos)
        ->and($tocBeforeSectionsPos)->toBeLessThan($approvalsPos)
        ->and($approvalsPos)->toBeLessThan($sectionsPos)
        ->and($sectionsPos)->toBeLessThan($changeHistoryPos)
        ->and(array_column(app(ReportFieldRegistry::class)->defaultGmpControlledDocumentFields(), 'key'))
        ->toContain('approvals', 'sections', 'change_history');

    $enabledOrder = collect(app(ReportFieldRegistry::class)->defaultGmpControlledDocumentFields())
        ->filter(fn (array $field): bool => $field['enabled'])
        ->pluck('key')
        ->all();

    expect($enabledOrder)->toBe(['approvals', 'sections', 'change_history']);
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

it('normalizes body block label and controlled section title visibility', function (): void {
    $registry = app(ReportFieldRegistry::class);

    $fields = $registry->normalize(ReportScope::ControlledDocument, [[
        'key' => 'sections',
        'enabled' => true,
        'show_label' => false,
        'show_section_titles' => false,
    ]]);

    expect($fields[0])
        ->toMatchArray([
            'key' => 'sections',
            'show_label' => false,
            'show_section_titles' => false,
        ])
        ->and($fields[1]['show_label'])->toBeTrue()
        ->and($fields[1]['show_section_titles'])->toBeTrue();
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
        ->and($headerZones['content_gap_mm'])->toBe(5)
        ->and($twoColumnZones['gap_mm'])->toBe(3)
        ->and($twoColumnZones['show_borders'])->toBeFalse()
        ->and($twoColumnZones['repeat_every_page'])->toBeTrue()
        ->and($twoColumnZones['content_gap_mm'])->toBe(5)
        ->and($twoColumnZones['rows'][0]['key'])->toBe('legacy_footer')
        ->and(array_column($twoColumnZones['rows'][0]['cells'], 'width'))->toBe([40, 60]);

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
        ->and($legacyTemplate->printFooterZones())->toHaveKeys(['gap_mm', 'show_borders', 'rows'])
        ->and($legacyTemplate->printHeaderZones()['rows'])->toHaveCount(6)
        ->and($legacyTemplate->printFooterZones()['rows'])->toHaveCount(1)
        ->and($legacyTemplate->printFooterZones()['content_gap_mm'])->toBe(15)
        ->and(array_column($legacyTemplate->printHeaderZones()['rows'][0]['cells'], 'width'))->toBe([20, 80])
        ->and(array_column($legacyTemplate->printFooterZones()['rows'][0]['cells'], 'width'))->toBe([38, 24, 38])
        ->and(collect($legacyTemplate->printHeaderZones()['rows'])->firstWhere('key', 'issuance'))->not->toBeNull()
        ->and(collect($legacyTemplate->printFooterZones()['rows'][0]['cells'][0]['items'])->pluck('token')->all())
        ->toContain('issuance_number');
});

it('exposes issuance number as a header, footer, and body block print field', function (): void {
    $layoutRegistry = app(PrintLayoutRegistry::class);
    $fieldRegistry = app(ReportFieldRegistry::class);

    expect($layoutRegistry->tokenOptions())
        ->toHaveKey('issuance_number')
        ->and($layoutRegistry->tokenOptions()['issuance_number'])->toBe('Issuance Number')
        ->and($fieldRegistry->definitions(ReportScope::ControlledDocument))
        ->toHaveKey('issuance_number')
        ->and($fieldRegistry->definitions(ReportScope::ControlledDocument)['issuance_number'])
        ->toMatchArray([
            'label' => 'Issuance Number',
            'group' => 'Metadata',
        ]);

    $normalizedZones = $layoutRegistry->normalizeZones([
        'rows' => [[
            'key' => 'issuance',
            'cells' => [
                ['key' => 'label', 'width' => 30, 'alignment' => 'left', 'vertical_alignment' => 'center', 'items' => [['token' => 'custom_text', 'custom_text' => 'Issuance No.']]],
                ['key' => 'value', 'width' => 70, 'alignment' => 'left', 'vertical_alignment' => 'center', 'items' => [['token' => 'issuance_number', 'show_label' => false]]],
            ],
        ]],
    ]);

    expect($normalizedZones['rows'][0]['cells'][1]['items'][0]['token'])->toBe('issuance_number');

    $fields = $fieldRegistry->normalize(ReportScope::ControlledDocument, [
        ['key' => 'issuance_number', 'enabled' => true, 'show_label' => true],
    ]);

    expect(collect($fields)->firstWhere('key', 'issuance_number'))
        ->toMatchArray([
            'key' => 'issuance_number',
            'enabled' => true,
            'label' => 'Issuance Number',
            'group' => 'Metadata',
        ]);

    $zone = file_get_contents(resource_path('views/reports/partials/print-zone.blade.php'));
    $print = file_get_contents(resource_path('views/controlled-documents/print.blade.php'));

    expect($zone)
        ->toContain("@case('issuance_number')")
        ->toContain('issuance?->issuance_number')
        ->and($print)
        ->toContain("in_array('issuance_number', \$enabledFields, true)");
});

it('normalizes configurable table of contents settings', function (): void {
    $registry = app(PrintLayoutRegistry::class);

    expect($registry->normalizeTocConfiguration([]))
        ->enabled->toBeFalse()
        ->title->toBe('Table of Contents')
        ->position->toBe('before_sections')
        ->max_level->toBe(3)
        ->show_section_numbers->toBeTrue();

    expect($registry->normalizeTocConfiguration([
        'enabled' => true,
        'title' => ' SOP Contents ',
        'position' => 'after_identity',
        'max_level' => 2,
        'show_section_numbers' => false,
        'page_break_before' => true,
        'page_break_after' => true,
    ]))->toBe([
        'enabled' => true,
        'title' => 'SOP Contents',
        'position' => 'after_identity',
        'show_section_numbers' => false,
        'page_break_before' => true,
        'page_break_after' => true,
        'max_level' => 2,
    ]);
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

it('renders real page counters for browser previews and generated pdf artifacts', function (): void {
    $view = file_get_contents(resource_path('views/controlled-documents/print.blade.php'));
    $zone = file_get_contents(resource_path('views/reports/partials/print-zone.blade.php'));

    expect($view)
        ->toContain('@bottom-right')
        ->toContain('counter(page)')
        ->toContain('counter(pages)')
        ->and($zone)
        ->toContain('@pageNumber')
        ->toContain('@totalPages')
        ->not->toContain('print preview shows the actual total');
});

it('does not print unused structured field configuration metadata', function (): void {
    $view = file_get_contents(resource_path('views/controlled-documents/print.blade.php'));

    expect($view)
        ->not->toContain('section-configuration')
        ->not->toContain('$section->configuration as');
});

it('hides table of contents settings when the table of contents is disabled', function (): void {
    $form = file_get_contents(app_path('Filament/Resources/ReportTemplates/Schemas/ReportTemplateForm.php'));

    expect($form)
        ->toContain("Toggle::make('toc_configuration.enabled')")
        ->toContain('->live()')
        ->toContain("->visible(fn (Get \$get): bool => (bool) \$get('toc_configuration.enabled'))")
        ->toContain("->required(fn (Get \$get): bool => (bool) \$get('toc_configuration.enabled'))");
});

it('reserves generated pdf margins and shares configured typography with headers and footers', function (): void {
    $documentView = file_get_contents(resource_path('views/controlled-documents/print.blade.php'));
    $headerView = file_get_contents(resource_path('views/controlled-documents/pdf-header.blade.php'));
    $footerView = file_get_contents(resource_path('views/controlled-documents/pdf-footer.blade.php'));
    $renderer = file_get_contents(app_path('Domain/DMS/Services/GotenbergControlledDocumentPdfRenderer.php'));

    expect($documentView)
        ->toContain("serverPdfMargins['top']")
        ->toContain('! ($serverPdf ?? false)')
        ->and($headerView)
        ->toContain("printPageSettings()['font_family']")
        ->toContain("printPageSettings()['font_size']")
        ->toContain("printPageSettings()['margin_top_mm']")
        ->toContain('pdf-header-offset')
        ->and($footerView)
        ->toContain("printPageSettings()['font_family']")
        ->toContain("printPageSettings()['font_size']")
        ->and($renderer)
        ->toContain("'serverPdfMargins'")
        ->toContain('HEADER_TEMPLATE_INSET_MM')
        ->toContain('estimatedHeaderHeight')
        ->toContain('estimatedFooterHeight');
});

it('uses the generated pdf measurements and shared partials in the template preview', function (): void {
    $preview = file_get_contents(resource_path('views/reports/template-preview.blade.php'));
    $header = file_get_contents(resource_path('views/reports/partials/print-header.blade.php'));
    $footer = file_get_contents(resource_path('views/reports/partials/print-footer.blade.php'));

    expect($preview)
        ->toContain("\$pageSettings['margin_top_mm']")
        ->toContain("\$headerZones['content_gap_mm']")
        ->toContain("\$footerZones['content_gap_mm']")
        ->toContain("@include('reports.partials.print-header', ['preview' => true])")
        ->toContain("@include('reports.partials.print-footer', ['preview' => true])")
        ->toContain('height: {{ $pageHeight }}mm')
        ->toContain('width: {{ $pageWidth }}mm')
        ->and($header)
        ->toContain("'preview' => \$preview ?? false")
        ->toContain('print-table')
        ->toContain('print-table-row')
        ->and($footer)
        ->toContain("'preview' => \$preview ?? false")
        ->toContain('print-table')
        ->toContain('print-table-row')
        ->toContain("\$footerZones['rows']");
});

it('repeats configured headers in the reserved print margin', function (): void {
    $view = file_get_contents(resource_path('views/controlled-documents/print.blade.php'));

    expect($view)
        ->toContain('<thead class="print-document-header">')
        ->toContain("@include('reports.partials.print-header')")
        ->toContain('display: table-header-group')
        ->toContain('print-header-flow-hidden')
        ->toContain('content_gap_mm')
        ->not->toContain('position: fixed')
        ->not->toContain('reserved_height_mm');
});

it('repeats configured footers with a configurable content gap', function (): void {
    $view = file_get_contents(resource_path('views/controlled-documents/print.blade.php'));

    expect($view)
        ->toContain('<tfoot class="print-document-footer">')
        ->toContain("@include('reports.partials.print-footer')")
        ->toContain('display: table-footer-group')
        ->toContain('print-footer-flow-hidden')
        ->toContain("configuredFooterZones['content_gap_mm']");
});
