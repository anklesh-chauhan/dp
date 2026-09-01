<?php

declare(strict_types=1);

use App\Domain\TMS\Enums\TrainingAssignmentSource;
use App\Domain\TMS\Models\RoleTrainingRequirement;
use App\Domain\TMS\Models\TrainingAssignment;
use App\Domain\TMS\Models\TrainingProgram;
use App\Domain\TMS\Models\TrainingProgramItem;
use App\Domain\TMS\Services\DocumentRetrainingService;
use App\Domain\TMS\Services\RoleTrainingMatrixService;
use App\Domain\TMS\Services\TrainingProgramAssignmentService;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\Designation;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    config()->set('modules.enabled', ['dms', 'tms']);

    Permission::findOrCreate('Assign:TrainingProgram', 'web');

    $this->coordinator = User::factory()->create();
    $this->coordinator->givePermissionTo('Assign:TrainingProgram');

    $this->trainee = User::factory()->create();
    $this->document = tmsApprovedDocument();
});

it('assigns a training program and creates document-level assignments', function (): void {
    $program = TrainingProgram::factory()->create([
        'code' => 'ONBOARDING',
        'created_by' => $this->coordinator->id,
    ]);

    TrainingProgramItem::query()->create([
        'training_program_id' => $program->id,
        'controlled_document_id' => $this->document->id,
        'is_required' => true,
        'sort_order' => 1,
    ]);

    $created = app(TrainingProgramAssignmentService::class)->assign(
        $program,
        $this->coordinator,
        [$this->trainee->id],
    );

    expect($created)->toHaveCount(1)
        ->and($created->first()?->source_type)->toBe(TrainingAssignmentSource::TrainingProgram)
        ->and(TrainingAssignment::query()
            ->where('source_type', TrainingAssignmentSource::ControlledDocument)
            ->where('controlled_document_id', $this->document->id)
            ->where('user_id', $this->trainee->id)
            ->exists())->toBeTrue();
});

it('schedules retraining when a superseding revision is approved', function (): void {
    $seriesId = (string) Str::uuid();
    $prior = tmsApprovedDocument([
        'document_series_id' => $seriesId,
        'version' => 1,
    ]);

    TrainingAssignment::query()->create([
        'source_type' => TrainingAssignmentSource::ControlledDocument,
        'controlled_document_id' => $prior->id,
        'user_id' => $this->trainee->id,
        'assigned_by' => $this->coordinator->id,
        'assigned_at' => now()->subDay(),
        'completed_at' => now()->subDay(),
    ]);

    $revision = tmsApprovedDocument([
        'document_series_id' => $seriesId,
        'version' => 2,
        'supersedes_document_id' => $prior->id,
    ]);

    app(DocumentRetrainingService::class)->scheduleForNewlyApprovedRevision(
        $revision->fresh()->load('supersedesDocument'),
        $this->coordinator,
    );

    expect(app(DocumentRetrainingService::class)->pendingRetrainingForDocument($revision))
        ->toHaveCount(1)
        ->and(app(DocumentRetrainingService::class)->pendingRetrainingForDocument($revision)->first()?->user_id)
        ->toBe($this->trainee->id);
});

it('builds role training matrix rows from designation requirements', function (): void {
    $designation = Designation::factory()->create([
        'code' => 'QA_REV',
        'name' => 'QA Reviewer',
    ]);

    $this->trainee->update(['designation_id' => $designation->id]);

    $program = TrainingProgram::factory()->create([
        'code' => 'GMP_CORE',
        'name' => 'GMP Core',
        'created_by' => $this->coordinator->id,
    ]);

    TrainingProgramItem::query()->create([
        'training_program_id' => $program->id,
        'controlled_document_id' => $this->document->id,
        'is_required' => true,
        'sort_order' => 1,
    ]);

    RoleTrainingRequirement::query()->create([
        'designation_id' => $designation->id,
        'training_program_id' => $program->id,
        'is_required' => true,
    ]);

    $rows = app(RoleTrainingMatrixService::class)->rows();

    expect($rows)->toHaveCount(1)
        ->and($rows->first())->toMatchArray([
            'designation_code' => 'QA_REV',
            'program_code' => 'GMP_CORE',
            'user_id' => $this->trainee->id,
            'assignment_status' => 'Pending',
        ]);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function tmsApprovedDocument(array $overrides = []): ControlledDocument
{
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail();
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department->id,
        'category_id' => $category->id,
        'document_type_id' => $documentType->id,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->id,
    ]);

    return ControlledDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'department_id' => $department->id,
        'category_id' => $category->id,
        'document_type_id' => $documentType->id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::APPROVED),
        ...$overrides,
    ]);
}
