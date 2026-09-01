<?php

declare(strict_types=1);

use App\Domain\DMS\Actions\AssignDocumentTrainingAction;
use App\Filament\Pages\MyTraining;
use App\Filament\Resources\TrainingAssignments\Pages\ListTrainingAssignments;
use App\Filament\Resources\TrainingAssignments\TrainingAssignmentResource;
use App\Filament\Support\MyTrainingQueueService;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentTrainingAssignment;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    config()->set('modules.enabled', ['dms', 'tms']);

    foreach ([
        'ViewAny:TrainingAssignment',
        'View:TrainingAssignment',
        'View:MyTraining',
        'Complete:TrainingAssignment',
        'AssignTraining:ControlledDocument',
        'ViewAny:ControlledDocument',
        'View:ControlledDocument',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->coordinator = User::factory()->create();
    $this->coordinator->assignRole(Role::findOrCreate('panel_user', 'web'));
    $this->coordinator->givePermissionTo([
        'ViewAny:TrainingAssignment',
        'View:TrainingAssignment',
        'AssignTraining:ControlledDocument',
        'ViewAny:ControlledDocument',
        'View:ControlledDocument',
    ]);

    $this->trainee = User::factory()->create();
    $this->trainee->assignRole(Role::findOrCreate('panel_user', 'web'));
    $this->trainee->givePermissionTo([
        'View:MyTraining',
        'Complete:TrainingAssignment',
    ]);

    $this->document = approvedTrainingDocument();
});

it('lists pending training for the signed-in trainee', function (): void {
    ControlledDocumentTrainingAssignment::factory()->create([
        'controlled_document_id' => $this->document->id,
        'user_id' => $this->trainee->id,
        'assigned_by' => $this->coordinator->id,
    ]);

    expect(app(MyTrainingQueueService::class)->pendingCountForUser($this->trainee))->toBe(1);

    $this->actingAs($this->trainee);

    Livewire::test(MyTraining::class)
        ->assertOk()
        ->assertCanSeeTableRecords(
            ControlledDocumentTrainingAssignment::query()
                ->where('user_id', $this->trainee->id)
                ->whereNull('completed_at')
                ->get(),
        );
});

it('completes training from My Training using the DMS action', function (): void {
    $assignment = ControlledDocumentTrainingAssignment::factory()->create([
        'controlled_document_id' => $this->document->id,
        'user_id' => $this->trainee->id,
        'assigned_by' => $this->coordinator->id,
    ]);

    $this->actingAs($this->trainee);

    Livewire::test(MyTraining::class)
        ->callAction(
            TestAction::make('completeTraining')->table($assignment),
            data: ['completion_comments' => 'Read and understood the approved SOP.'],
        )
        ->assertNotified();

    expect($assignment->refresh()->completed_at)->not->toBeNull()
        ->and($assignment->completion_comments)->toBe('Read and understood the approved SOP.');
});

it('shows a navigation badge for pending training', function (): void {
    ControlledDocumentTrainingAssignment::factory()->create([
        'controlled_document_id' => $this->document->id,
        'user_id' => $this->trainee->id,
        'assigned_by' => $this->coordinator->id,
    ]);

    $this->actingAs($this->trainee);

    expect(MyTraining::getNavigationBadge())->toBe('1');
});

it('lets coordinators browse all training assignments', function (): void {
    $assignment = ControlledDocumentTrainingAssignment::factory()->create([
        'controlled_document_id' => $this->document->id,
        'user_id' => $this->trainee->id,
        'assigned_by' => $this->coordinator->id,
    ]);

    $this->actingAs($this->coordinator);

    Livewire::test(ListTrainingAssignments::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$assignment]);
});

it('blocks trainees from the coordinator assignment index', function (): void {
    Permission::findOrCreate('ViewAny:TrainingAssignment', 'web');
    Permission::findOrCreate('View:TrainingAssignment', 'web');

    $this->actingAs($this->trainee);

    expect(TrainingAssignmentResource::canViewAny())->toBeFalse();
});

it('creates assignments through the existing DMS service', function (): void {
    $created = app(AssignDocumentTrainingAction::class)->execute(
        $this->document,
        $this->coordinator,
        [$this->trainee->id],
    );

    expect($created)->toHaveCount(1)
        ->and($created->first()?->user_id)->toBe($this->trainee->id)
        ->and(app(MyTrainingQueueService::class)->pendingCountForUser($this->trainee))->toBe(1);
});

function approvedTrainingDocument(): ControlledDocument
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
    ]);
}
