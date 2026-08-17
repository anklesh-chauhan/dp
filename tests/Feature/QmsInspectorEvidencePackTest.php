<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Models\SiteMasterFile;
use App\Domain\QMS\Services\InspectorEvidencePackService;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    foreach (['Export:InspectorEvidencePack', 'View:ScheduleMGapAssessment', 'View:QualityMetrics'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo(['Export:InspectorEvidencePack', 'View:QualityMetrics']);
});

it('builds a structured inspector evidence pack from a gap assessment', function (): void {
    $assessment = ScheduleMGapAssessment::factory()->withPartIChecklist()->create([
        'site_name' => 'Plant A',
    ]);
    $assessment->items()->first()?->update([
        'status' => ScheduleMGapItemStatus::Gap,
        'due_at' => now()->addWeek(),
    ]);

    CsvValidationProject::factory()->create();

    $pack = app(InspectorEvidencePackService::class)->build($assessment, $this->actor);

    expect($pack['source']['type'])->toBe('schedule_m_gap_assessment')
        ->and($pack['source']['number'])->toBe($assessment->assessment_number)
        ->and($pack['gap_summary']['site_name'])->toBe('Plant A')
        ->and($pack['gap_summary']['item_counts'])->toHaveKey(ScheduleMGapItemStatus::NotAssessed->value)
        ->and($pack['gap_summary']['open_gaps'])->not->toBeEmpty()
        ->and($pack)->toHaveKeys([
            'generated_at',
            'generated_by',
            'sop_index',
            'open_quality_event_counts',
            'csv_validation_counts',
            'training_sample',
        ])
        ->and($pack['csv_validation_counts'])->not->toBeNull();
});

it('builds a pack from a site master file and allows view gated export', function (): void {
    $file = SiteMasterFile::factory()->create([
        'sections' => [
            ...SiteMasterFile::defaultSections(),
            'site_information' => ['content' => 'Overview', 'document_id' => null],
        ],
    ]);

    $viewer = User::factory()->create();
    $viewer->givePermissionTo(['View:ScheduleMGapAssessment', 'View:QualityMetrics']);

    $pack = app(InspectorEvidencePackService::class)->build($file, $viewer);

    expect($pack['source']['type'])->toBe('site_master_file')
        ->and($pack['site_master_file']['smf_number'])->toBe($file->smf_number)
        ->and($pack['gap_summary'])->toBeNull();
});

it('blocks pack export when qms is disabled or permission is missing', function (): void {
    $service = app(InspectorEvidencePackService::class);

    config()->set('modules.enabled', ['dms']);
    expect(fn () => $service->build(null, $this->actor))
        ->toThrow(ModuleNotEnabledException::class);

    config()->set('modules.enabled', ['dms', 'qms']);
    expect(fn () => $service->build(null, User::factory()->create()))
        ->toThrow(AuthorizationException::class);
});
