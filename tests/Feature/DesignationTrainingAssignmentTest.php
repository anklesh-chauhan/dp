<?php

declare(strict_types=1);

use App\Domain\TMS\Enums\TrainingAssignmentSource;
use App\Domain\TMS\Models\RoleTrainingRequirement;
use App\Domain\TMS\Models\TrainingAssignment;
use App\Domain\TMS\Models\TrainingProgram;
use App\Domain\TMS\Models\TrainingProgramItem;
use App\Domain\TMS\Services\DesignationTrainingAssignmentService;
use App\Filament\Support\MyTrainingQueueService;
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
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    config()->set('modules.enabled', ['dms', 'tms']);

    Permission::findOrCreate('AssignTraining:ControlledDocument', 'web');

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('AssignTraining:ControlledDocument');

    $this->actingAs($this->admin);

    $this->document = designationTrainingApprovedDocument();
    $this->designation = Designation::factory()->create([
        'code' => 'QA_REV',
        'name' => 'QA Reviewer',
    ]);
    $this->program = TrainingProgram::factory()->create([
        'code' => 'GMP_CORE',
        'name' => 'GMP Core',
        'created_by' => $this->admin->id,
    ]);

    TrainingProgramItem::query()->create([
        'training_program_id' => $this->program->id,
        'controlled_document_id' => $this->document->id,
        'is_required' => true,
        'sort_order' => 1,
    ]);

    RoleTrainingRequirement::query()->create([
        'designation_id' => $this->designation->id,
        'training_program_id' => $this->program->id,
        'is_required' => true,
    ]);
});

it('assigns required role-matrix programs when a user receives a designation', function (): void {
    $trainee = User::factory()->create([
        'designation_id' => $this->designation->id,
    ]);

    expect(TrainingAssignment::query()
        ->where('training_program_id', $this->program->id)
        ->where('user_id', $trainee->id)
        ->exists())->toBeTrue()
        ->and(TrainingAssignment::query()
            ->where('source_type', TrainingAssignmentSource::ControlledDocument)
            ->where('controlled_document_id', $this->document->id)
            ->where('user_id', $trainee->id)
            ->exists())->toBeTrue()
        ->and(app(MyTrainingQueueService::class)->pendingCountForUser($trainee))->toBe(1);
});

it('assigns programs when an existing user designation changes', function (): void {
    $trainee = User::factory()->create();

    expect(app(MyTrainingQueueService::class)->pendingCountForUser($trainee))->toBe(0);

    $trainee->update(['designation_id' => $this->designation->id]);

    expect(app(MyTrainingQueueService::class)->pendingCountForUser($trainee))->toBe(1);
});

it('does not duplicate assignments when sync runs again', function (): void {
    $trainee = User::factory()->create([
        'designation_id' => $this->designation->id,
    ]);

    $created = app(DesignationTrainingAssignmentService::class)->syncForUser($trainee, $this->admin);

    expect($created)->toBeEmpty()
        ->and(TrainingAssignment::query()
            ->where('training_program_id', $this->program->id)
            ->where('user_id', $trainee->id)
            ->count())->toBe(1)
        ->and(TrainingAssignment::query()
            ->where('source_type', TrainingAssignmentSource::ControlledDocument)
            ->where('controlled_document_id', $this->document->id)
            ->where('user_id', $trainee->id)
            ->count())->toBe(1);
});

it('does nothing when TMS is disabled', function (): void {
    config()->set('modules.enabled', ['dms']);

    $trainee = User::factory()->create([
        'designation_id' => $this->designation->id,
    ]);

    expect(TrainingAssignment::query()->where('user_id', $trainee->id)->count())->toBe(0);
});

it('skips inactive programs linked to the designation', function (): void {
    $this->program->update(['is_active' => false]);

    $trainee = User::factory()->create([
        'designation_id' => $this->designation->id,
    ]);

    expect(TrainingAssignment::query()->where('user_id', $trainee->id)->count())->toBe(0);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function designationTrainingApprovedDocument(array $overrides = []): ControlledDocument
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
