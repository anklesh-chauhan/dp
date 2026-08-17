<?php

declare(strict_types=1);

use App\Domain\DMS\Services\DocumentExecutionService;
use App\Domain\QMS\Enums\EquipmentAssetCriticality;
use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Domain\QMS\Models\EquipmentCalibration;
use App\Domain\QMS\Services\CalibrationGate;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentExecution;
use App\Models\DocumentIssuance;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    config()->set('modules.calibration_gate', true);
    $this->seed(LookupTableSeeder::class);
    $this->actor = User::factory()->create();
});

it('fails open when no critical equipment assets exist', function (): void {
    EquipmentAsset::factory()->create(['criticality' => EquipmentAssetCriticality::High]);
    EquipmentCalibration::factory()->create([
        'status' => EquipmentCalibrationStatus::Scheduled,
        'due_at' => now()->subDay(),
        'equipment_asset_id' => EquipmentAsset::factory()->create([
            'criticality' => EquipmentAssetCriticality::Medium,
        ]),
    ]);

    app(CalibrationGate::class)->assertNoOverdueCriticalCalibrations($this->actor);

    $approved = app(DocumentExecutionService::class)->qaApprove(
        calibrationGateQaReviewExecution(),
        $this->actor,
        DocumentExecution::DISPOSITION_RELEASED,
        'Released with no critical assets.',
    );

    expect($approved->status)->toBe(DocumentExecution::STATUS_CLOSED);
});

it('blocks qaApprove when a critical asset has an overdue calibration', function (): void {
    $critical = EquipmentAsset::factory()->create([
        'criticality' => EquipmentAssetCriticality::Critical,
    ]);
    EquipmentCalibration::factory()->create([
        'equipment_asset_id' => $critical,
        'status' => EquipmentCalibrationStatus::Scheduled,
        'due_at' => now()->subDay(),
    ]);

    expect(fn () => app(CalibrationGate::class)->assertNoOverdueCriticalCalibrations($this->actor))
        ->toThrow(ValidationException::class);

    expect(fn () => app(DocumentExecutionService::class)->qaApprove(
        calibrationGateQaReviewExecution(),
        $this->actor,
        DocumentExecution::DISPOSITION_RELEASED,
        'Should be blocked.',
    ))->toThrow(ValidationException::class);
});

it('allows qaApprove when critical calibrations are current', function (): void {
    $critical = EquipmentAsset::factory()->create([
        'criticality' => EquipmentAssetCriticality::Critical,
    ]);
    EquipmentCalibration::factory()->create([
        'equipment_asset_id' => $critical,
        'status' => EquipmentCalibrationStatus::Scheduled,
        'due_at' => now()->addWeek(),
    ]);

    $approved = app(DocumentExecutionService::class)->qaApprove(
        calibrationGateQaReviewExecution(),
        $this->actor,
        DocumentExecution::DISPOSITION_RELEASED,
        'Released with current calibrations.',
    );

    expect($approved->status)->toBe(DocumentExecution::STATUS_CLOSED);
});

it('skips the overdue gate when calibration_gate config is disabled', function (): void {
    config()->set('modules.calibration_gate', false);

    $critical = EquipmentAsset::factory()->create([
        'criticality' => EquipmentAssetCriticality::Critical,
    ]);
    EquipmentCalibration::factory()->create([
        'equipment_asset_id' => $critical,
        'status' => EquipmentCalibrationStatus::InProgress,
        'due_at' => now()->subDay(),
    ]);

    expect(fn () => app(CalibrationGate::class)->assertNoOverdueCriticalCalibrations($this->actor))
        ->not->toThrow(ValidationException::class);
});

function calibrationGateQaReviewExecution(): DocumentExecution
{
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::query()->where('code', DocumentType::BATCH_RECORD)->firstOrFail();
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create(['document_template_id' => $template]);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
    ]);
    $issuance = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issuance_type' => DocumentIssuance::TYPE_EXECUTION,
    ]);

    return DocumentExecution::factory()->create([
        'document_issuance_id' => $issuance,
        'document_type_code' => DocumentType::BATCH_RECORD,
        'workflow_configuration' => [
            'requires_qa_approval' => true,
            'requires_disposition' => true,
        ],
        'status' => DocumentExecution::STATUS_QA_REVIEW,
        'completed_by' => User::factory(),
        'reviewed_by' => User::factory(),
        'disposition' => DocumentExecution::DISPOSITION_PENDING,
    ]);
}
