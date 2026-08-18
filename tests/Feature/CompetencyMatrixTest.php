<?php

declare(strict_types=1);

use App\Domain\DMS\Services\DocumentExecutionService;
use App\Domain\QMS\Enums\UserCompetencyStatus;
use App\Domain\QMS\Models\CompetencyCurriculum;
use App\Domain\QMS\Models\CompetencyCurriculumItem;
use App\Domain\QMS\Models\UserCompetency;
use App\Domain\QMS\Services\CompetencyGate;
use App\Domain\QMS\Services\CompetencyService;
use App\Filament\Resources\CompetencyCurricula\CompetencyCurriculumResource;
use App\Filament\Resources\UserCompetencies\UserCompetencyResource;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentTrainingAssignment;
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
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    $this->seed(LookupTableSeeder::class);

    foreach (['Assign:UserCompetency', 'Assign:CompetencyCurriculum', 'Verify:UserCompetency'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo(['Assign:UserCompetency', 'Verify:UserCompetency']);
    $this->trainee = User::factory()->create();
    $this->document = competencyApprovedDocument();
});

it('installs competency schema and module permissions', function (): void {
    expect(Schema::hasColumns('competency_curricula', [
        'code',
        'name',
        'role_name',
        'sop_role_id',
        'requalification_months',
        'gate_key',
        'is_active',
        'created_by',
    ]))->toBeTrue()
        ->and(Schema::hasTable('competency_curriculum_items'))->toBeTrue()
        ->and(Schema::hasTable('user_competencies'))->toBeTrue()
        ->and(QmsModuleSeeder::PERMISSIONS)->toContain(
            'ViewAny:CompetencyCurriculum',
            'Assign:CompetencyCurriculum',
            'ViewAny:UserCompetency',
            'Assign:UserCompetency',
            'Verify:UserCompetency',
            'Manage:UserCompetency',
        )
        ->and(CompetencyCurriculumResource::getNavigationSort())->toBe(20)
        ->and(UserCompetencyResource::getNavigationSort())->toBe(21)
        ->and(CompetencyCurriculumResource::getNavigationGroup())->toBe('QMS');
});

it('assigns a curriculum and marks trained after required document training is complete', function (): void {
    $curriculum = CompetencyCurriculum::factory()->create([
        'code' => 'GMP_EXECUTION',
        'role_name' => 'QA Approver',
        'requalification_months' => 12,
        'created_by' => $this->actor->id,
    ]);

    CompetencyCurriculumItem::factory()->create([
        'curriculum_id' => $curriculum->id,
        'controlled_document_id' => $this->document->id,
        'is_required' => true,
    ]);

    $service = app(CompetencyService::class);
    $competency = $service->assignCurriculum($this->trainee, $curriculum, $this->actor);

    expect($competency->status)->toBe(UserCompetencyStatus::Assigned);

    ControlledDocumentTrainingAssignment::factory()->create([
        'document_id' => $this->document->id,
        'user_id' => $this->trainee->id,
        'assigned_by' => $this->actor->id,
        'assigned_at' => now(),
        'completed_at' => now(),
    ]);

    $refreshed = $service->refreshUserCompetency($competency->fresh(['curriculum.items']));

    expect($refreshed->status)->toBe(UserCompetencyStatus::Trained)
        ->and($refreshed->trained_at)->not->toBeNull()
        ->and($refreshed->expires_at)->not->toBeNull()
        ->and($refreshed->isCurrent())->toBeTrue();

    $service->assertCompetent($this->trainee, 'GMP_EXECUTION');
});

it('marks competency expired when past expires_at', function (): void {
    $curriculum = CompetencyCurriculum::factory()->create([
        'requalification_months' => 12,
        'created_by' => $this->actor->id,
    ]);

    CompetencyCurriculumItem::factory()->create([
        'curriculum_id' => $curriculum->id,
        'controlled_document_id' => $this->document->id,
        'is_required' => true,
    ]);

    ControlledDocumentTrainingAssignment::factory()->create([
        'document_id' => $this->document->id,
        'user_id' => $this->trainee->id,
        'completed_at' => now()->subYears(2),
    ]);

    $competency = UserCompetency::factory()->create([
        'user_id' => $this->trainee->id,
        'curriculum_id' => $curriculum->id,
        'status' => UserCompetencyStatus::Trained,
        'trained_at' => now()->subYears(2),
        'expires_at' => now()->subDay(),
        'assigned_by' => $this->actor->id,
    ]);

    $refreshed = app(CompetencyService::class)->refreshUserCompetency($competency->fresh(['curriculum.items']));

    expect($refreshed->status)->toBe(UserCompetencyStatus::Expired)
        ->and($refreshed->isCurrent())->toBeFalse();

    expect(fn () => app(CompetencyService::class)->assertCompetent($this->trainee, $curriculum))
        ->toThrow(AuthorizationException::class);
});

it('fails open for qaApprove when no active gate curriculum exists', function (): void {
    CompetencyCurriculum::factory()->create([
        'code' => 'GMP_EXECUTION',
        'gate_key' => CompetencyCurriculum::GATE_DOCUMENT_EXECUTION_QA,
        'is_active' => false,
    ]);

    $execution = competencyQaReviewExecution();

    $approved = app(DocumentExecutionService::class)->qaApprove(
        $execution,
        $this->actor,
        DocumentExecution::DISPOSITION_RELEASED,
        'Released under fail-open competency gate.',
    );

    expect($approved->status)->toBe(DocumentExecution::STATUS_CLOSED)
        ->and($approved->qa_approved_by)->toBe($this->actor->id);
});

it('blocks qaApprove when active document_execution_qa competency is overdue', function (): void {
    $curriculum = CompetencyCurriculum::factory()->forGate(CompetencyCurriculum::GATE_DOCUMENT_EXECUTION_QA)->create([
        'code' => 'GMP_EXECUTION',
        'requalification_months' => 12,
        'created_by' => $this->actor->id,
    ]);

    CompetencyCurriculumItem::factory()->create([
        'curriculum_id' => $curriculum->id,
        'controlled_document_id' => $this->document->id,
        'is_required' => true,
    ]);

    ControlledDocumentTrainingAssignment::factory()->create([
        'document_id' => $this->document->id,
        'user_id' => $this->actor->id,
        'completed_at' => now()->subYears(2),
    ]);

    UserCompetency::factory()->expired()->create([
        'user_id' => $this->actor->id,
        'curriculum_id' => $curriculum->id,
        'assigned_by' => $this->actor->id,
    ]);

    $execution = competencyQaReviewExecution();

    expect(fn () => app(DocumentExecutionService::class)->qaApprove(
        $execution,
        $this->actor,
        DocumentExecution::DISPOSITION_RELEASED,
    ))->toThrow(AuthorizationException::class);
});

it('allows qaApprove when active document_execution_qa competency is trained', function (): void {
    $curriculum = CompetencyCurriculum::factory()->forGate(CompetencyCurriculum::GATE_DOCUMENT_EXECUTION_QA)->create([
        'code' => 'GMP_EXECUTION',
        'requalification_months' => 12,
        'created_by' => $this->actor->id,
    ]);

    CompetencyCurriculumItem::factory()->create([
        'curriculum_id' => $curriculum->id,
        'controlled_document_id' => $this->document->id,
        'is_required' => true,
    ]);

    ControlledDocumentTrainingAssignment::factory()->create([
        'document_id' => $this->document->id,
        'user_id' => $this->actor->id,
        'completed_at' => now(),
    ]);

    app(CompetencyService::class)->assignCurriculum($this->actor, $curriculum, $this->actor);

    $execution = competencyQaReviewExecution();

    $approved = app(DocumentExecutionService::class)->qaApprove(
        $execution,
        $this->actor,
        DocumentExecution::DISPOSITION_RELEASED,
        'Competent QA release.',
    );

    expect($approved->status)->toBe(DocumentExecution::STATUS_CLOSED)
        ->and($approved->disposition)->toBe(DocumentExecution::DISPOSITION_RELEASED);
});

it('lists competencies by role', function (): void {
    $curriculum = CompetencyCurriculum::factory()->create([
        'role_name' => 'Production Operator',
        'created_by' => $this->actor->id,
    ]);

    UserCompetency::factory()->create([
        'user_id' => $this->trainee->id,
        'curriculum_id' => $curriculum->id,
        'assigned_by' => $this->actor->id,
    ]);

    $rows = app(CompetencyService::class)->competencyByRole();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['role'])->toBe('Production Operator')
        ->and($rows->first()['user_id'])->toBe($this->trainee->id)
        ->and($rows->first()['status'])->toBe(UserCompetencyStatus::Assigned->value);
});

it('enforces QMS entitlement on competency filament resources', function (): void {
    foreach ([
        'ViewAny:CompetencyCurriculum',
        'ViewAny:UserCompetency',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor->givePermissionTo([
        'ViewAny:CompetencyCurriculum',
        'ViewAny:UserCompetency',
    ]);
    $this->actingAs($this->actor);

    expect(CompetencyCurriculumResource::canAccess())->toBeTrue()
        ->and(UserCompetencyResource::canAccess())->toBeTrue();

    config()->set('modules.enabled', ['dms']);

    expect(CompetencyCurriculumResource::canAccess())->toBeFalse()
        ->and(UserCompetencyResource::shouldRegisterNavigation())->toBeFalse();
});

it('seeds active default gate curricula that fail open until required SOPs are linked', function (): void {
    $this->seed(QmsModuleSeeder::class);

    $executionGate = CompetencyCurriculum::query()->where('code', 'GMP_EXECUTION')->first();
    $changeGate = CompetencyCurriculum::query()->where('code', 'CHANGE_CONTROL_APPROVE')->first();

    expect($executionGate)->not->toBeNull()
        ->and($executionGate->is_active)->toBeTrue()
        ->and($executionGate->gate_key)->toBe(CompetencyCurriculum::GATE_DOCUMENT_EXECUTION_QA)
        ->and($changeGate)->not->toBeNull()
        ->and($changeGate->is_active)->toBeTrue()
        ->and($changeGate->gate_key)->toBe(CompetencyCurriculum::GATE_CHANGE_CONTROL_APPROVE);

    app(CompetencyGate::class)->assert($this->actor, CompetencyCurriculum::GATE_DOCUMENT_EXECUTION_QA);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function competencyApprovedDocument(array $overrides = []): ControlledDocument
{
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail();
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
    ]);

    return ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::APPROVED),
        ...$overrides,
    ]);
}

function competencyQaReviewExecution(): DocumentExecution
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
