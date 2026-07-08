<?php

declare(strict_types=1);

use App\Enums\NumberSeriesOverflowBehavior;
use App\Exceptions\NumberSeriesOverflowException;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\NumberSeries;
use App\Models\NumberSeriesCounter;
use App\Models\NumberSeriesSetting;
use App\Services\NumberSeries\NumberSeriesService;
use App\Services\Sop\DocumentNumberGeneratorService;
use App\Support\NumberSeries\NumberSeriesRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    NumberSeriesSetting::current();
});

it('generates document numbers for sop documents using the default pattern', function (): void {
    $department = Department::factory()->create(['code' => 'QA']);
    $service = app(NumberSeriesService::class);

    expect($service->generate($department, DocumentType::SOP))->toBe('SOP-QA-00001')
        ->and($service->generate($department, DocumentType::SOP))->toBe('SOP-QA-00002');
});

it('generates document numbers for all document type codes', function (): void {
    $department = Department::factory()->create(['code' => 'PROD']);
    $service = app(NumberSeriesService::class);

    $types = ['SOP', 'LOG', 'BMR', 'FORM', 'REPORT', 'PROTOCOL', 'SPEC', 'VALIDATION', 'CHANGE_CONTROL', 'CAPA', 'DEV', 'INCIDENT', 'AUDIT', 'INSPECTION', 'TEST', 'TRAINING', 'OTHER'];

    foreach ($types as $type) {
        expect($service->generate($department, $type))->toBe("{$type}-PROD-00001");
    }
});

it('keeps counters isolated per document type and department', function (): void {
    $qa = Department::factory()->create(['code' => 'QA']);
    $prod = Department::factory()->create(['code' => 'PROD']);
    $service = app(NumberSeriesService::class);

    $service->generate($qa, 'SOP');
    $service->generate($qa, 'LOG');
    $service->generate($prod, 'SOP');

    expect(NumberSeriesCounter::query()->pluck('last_number', 'series_type')->all())->toMatchArray([
        'SOP:QA' => 1,
        'LOG:QA' => 1,
        'SOP:PROD' => 1,
    ]);
});

it('increments from the last persisted counter value', function (): void {
    $department = Department::factory()->create(['code' => 'QA']);

    NumberSeriesCounter::query()->create([
        'series_type' => 'SOP:QA',
        'last_number' => 122,
    ]);

    $service = app(NumberSeriesService::class);

    expect($service->generate($department, 'SOP'))->toBe('SOP-QA-00123');
});

it('applies per-type configuration overrides', function (): void {
    $documentType = DocumentType::query()->firstOrCreate(
        ['code' => 'BMR'],
        ['name' => 'Batch Manufacturing Record', 'requires_sop_reference' => true, 'is_issuable' => true],
    );

    NumberSeries::query()->updateOrCreate(
        ['document_type_id' => $documentType->id],
        [
            'prefix_pattern' => 'BMR/{department}/',
            'padding_length' => 4,
            'suffix' => '/V1',
        ],
    );

    app()->forgetInstance(NumberSeriesRegistry::class);

    $department = Department::factory()->create(['code' => 'QA']);
    $service = app(NumberSeriesService::class);

    expect($service->generate($department, 'BMR'))->toBe('BMR/QA/0001/V1');
});

it('expands padding when overflow behavior is set to expand', function (): void {
    NumberSeriesSetting::current()->update([
        'overflow_behavior' => NumberSeriesOverflowBehavior::Expand,
    ]);

    $documentType = DocumentType::query()->firstOrCreate(
        ['code' => 'LOG'],
        ['name' => 'Log Document', 'requires_sop_reference' => true, 'is_issuable' => true],
    );

    NumberSeries::query()->updateOrCreate(
        ['document_type_id' => $documentType->id],
        ['padding_length' => 4],
    );

    app()->forgetInstance(NumberSeriesRegistry::class);

    $department = Department::factory()->create(['code' => 'QA']);

    NumberSeriesCounter::query()->create([
        'series_type' => 'LOG:QA',
        'last_number' => 9999,
    ]);

    $service = app(NumberSeriesService::class);

    expect($service->generate($department, 'LOG'))->toBe('LOG-QA-10000');
});

it('throws a safe overflow exception when padding cannot expand', function (): void {
    NumberSeriesSetting::current()->update([
        'overflow_behavior' => NumberSeriesOverflowBehavior::Throw,
    ]);

    $documentType = DocumentType::query()->firstOrCreate(
        ['code' => 'FORM'],
        ['name' => 'Controlled Form', 'requires_sop_reference' => true, 'is_issuable' => true],
    );

    NumberSeries::query()->updateOrCreate(
        ['document_type_id' => $documentType->id],
        ['padding_length' => 3],
    );

    app()->forgetInstance(NumberSeriesRegistry::class);

    $department = Department::factory()->create(['code' => 'QA']);

    NumberSeriesCounter::query()->create([
        'series_type' => 'FORM:QA',
        'last_number' => 999,
    ]);

    $service = app(NumberSeriesService::class);

    expect(fn () => $service->generate($department, 'FORM'))
        ->toThrow(NumberSeriesOverflowException::class);
});

it('previews the next number without incrementing the counter', function (): void {
    $department = Department::factory()->create(['code' => 'QA']);

    NumberSeriesCounter::query()->create([
        'series_type' => 'SOP:QA',
        'last_number' => 41,
    ]);

    $service = app(NumberSeriesService::class);

    expect($service->peekNext($department, 'SOP'))->toBe('SOP-QA-00042')
        ->and(NumberSeriesCounter::query()->where('series_type', 'SOP:QA')->value('last_number'))
        ->toBe(41);
});

it('synchronizes counters from external state', function (): void {
    $department = Department::factory()->create(['code' => 'DEV']);
    $service = app(NumberSeriesService::class);

    $service->synchronizeCounter($department, 'CAPA', 4);

    expect($service->generate($department, 'CAPA'))->toBe('CAPA-DEV-00005');
});

it('generates numbers through the document number generator service', function (): void {
    $department = Department::factory()->create(['code' => 'QA']);
    $generator = app(DocumentNumberGeneratorService::class);

    expect($generator->generate($department, 'SOP'))->toBe('SOP-QA-00001')
        ->and($generator->generate($department, 'LOG'))->toBe('LOG-QA-00001');
});
