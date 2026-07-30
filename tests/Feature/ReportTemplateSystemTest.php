<?php

declare(strict_types=1);

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Services\TabularReportExporter;
use App\Domain\Reporting\Support\PrintLayoutRegistry;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Models\ControlledDocument;
use App\Models\ReportTemplate;
use Database\Seeders\ReportTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('seeds the GMP and ALCOA plus standard template library', function (): void {
    $this->seed(ReportTemplateSeeder::class);

    expect(ReportTemplate::query()->count())->toBe(5)
        ->and(ReportTemplate::query()->where('layout_key', 'sop-gmp-standard')->value('is_system'))->toBeTrue()
        ->and(ReportTemplate::query()->where('scope', ReportScope::DocumentDistribution)->count())->toBe(3);
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
    $legacyZones = $registry->normalizeZones([
        'left' => [['token' => 'logo']],
        'center' => [['token' => 'custom_text', 'custom_text' => '  Quality System Master Copy  ']],
        'right' => [['token' => 'document_number']],
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
    ]);

    expect($settings)
        ->orientation->toBe('landscape')
        ->primary_color->toBe('#123abc')
        ->margin_left_mm->toBe(22)
        ->and(array_column($legacyZones['columns'], 'width'))->toBe([33, 34, 33])
        ->and($legacyZones['columns'][1]['items'][0]['custom_text'])->toBe('Quality System Master Copy')
        ->and($twoColumnZones['gap_mm'])->toBe(3)
        ->and($twoColumnZones['show_borders'])->toBeFalse()
        ->and(array_column($twoColumnZones['columns'], 'width'))->toBe([40, 60]);

    expect(fn () => $registry->normalizeZones([
        'left' => [['token' => 'unsafe_html']],
    ]))->toThrow(ValidationException::class);

    expect(fn () => $registry->normalizeZones([
        'columns' => [
            ['key' => 'one', 'width' => 70, 'alignment' => 'left', 'vertical_alignment' => 'top', 'items' => []],
            ['key' => 'two', 'width' => 20, 'alignment' => 'right', 'vertical_alignment' => 'bottom', 'items' => []],
        ],
    ]))->toThrow(ValidationException::class);

    $legacyTemplate = new ReportTemplate;

    expect($legacyTemplate->printPageSettings()['paper_size'])->toBe('a4')
        ->and($legacyTemplate->printHeaderZones())->toHaveKeys(['gap_mm', 'show_borders', 'columns'])
        ->and($legacyTemplate->printFooterZones())->toHaveKeys(['gap_mm', 'show_borders', 'columns'])
        ->and($legacyTemplate->printHeaderZones()['columns'])->toHaveCount(3);
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

it('renders page counters in the printed page margin', function (): void {
    $view = file_get_contents(resource_path('views/controlled-documents/print.blade.php'));

    expect($view)
        ->toContain('@bottom-right')
        ->toContain('counter(page)')
        ->toContain('counter(pages)');
});
