<?php

declare(strict_types=1);

use App\Domain\DMS\Actions\ActivateDueControlledDocumentsAction;
use App\Domain\DMS\Actions\AssignDocumentTrainingAction;
use App\Domain\DMS\Actions\CompleteDocumentTrainingAction;
use App\Domain\DMS\Actions\MakeDocumentEffectiveAction;
use App\Filament\Resources\ControlledDocuments\Pages\ViewControlledDocument;
use App\Filament\Resources\ControlledDocuments\RelationManagers\TrainingAssignmentsRelationManager;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\SopAuditLog;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    config()->set('modules.enabled', ['dms']);

    foreach ([
        'ViewAny:ControlledDocument',
        'View:ControlledDocument',
        'AssignTraining:ControlledDocument',
        'MakeEffective:ControlledDocument',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->controller = User::factory()->create();
    $this->controller->assignRole(Role::findOrCreate('panel_user', 'web'));
    $this->controller->givePermissionTo([
        'ViewAny:ControlledDocument',
        'View:ControlledDocument',
        'AssignTraining:ControlledDocument',
        'MakeEffective:ControlledDocument',
    ]);
    $this->trainee = User::factory()->create();
    $this->document = approvedControlledDocument();
});

it('does not make a document effective until required training is complete', function (): void {
    expect($this->document->documentStatus?->code)->toBe(DocumentStatus::APPROVED)
        ->and(fn () => app(MakeDocumentEffectiveAction::class)->execute(
            $this->document,
            $this->controller,
            now()->toDateString(),
        ))->toThrow(ValidationException::class, 'Required training must be assigned');

    app(AssignDocumentTrainingAction::class)->execute(
        $this->document,
        $this->controller,
        [$this->trainee->id],
    );

    expect(fn () => app(MakeDocumentEffectiveAction::class)->execute(
        $this->document,
        $this->controller,
        now()->toDateString(),
    ))->toThrow(ValidationException::class, 'All required training must be completed');

    expect($this->document->refresh()->documentStatus?->code)->toBe(DocumentStatus::APPROVED);
});

it('makes a document effective after training is completed and document control confirms the date', function (): void {
    $seriesId = (string) Str::uuid();
    $prior = approvedControlledDocument([
        'document_series_id' => $seriesId,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'version' => 1,
        'effective_date' => now()->subMonth()->toDateString(),
    ]);
    $document = approvedControlledDocument([
        'document_series_id' => $seriesId,
        'version' => 2,
        'supersedes_document_id' => $prior->id,
    ]);

    $assignment = app(AssignDocumentTrainingAction::class)->execute(
        $document,
        $this->controller,
        [$this->trainee->id],
    )->firstOrFail();

    app(CompleteDocumentTrainingAction::class)->execute(
        $assignment,
        $this->trainee,
        'I have read and understood this procedure.',
    );

    $released = app(MakeDocumentEffectiveAction::class)->execute(
        $document,
        $this->controller,
        now()->toDateString(),
        'Training complete. Release for use.',
    );

    expect($released->documentStatus?->code)->toBe(DocumentStatus::EFFECTIVE)
        ->and($released->effective_date?->toDateString())->toBe(now()->toDateString())
        ->and($released->released_for_effectiveness_by)->toBe($this->controller->id)
        ->and($prior->refresh()->documentStatus?->code)->toBe(DocumentStatus::SUPERSEDED)
        ->and(SopAuditLog::query()->where('document_id', $document->id)->where('action', SopAuditLog::ACTION_TRAINING_ASSIGNED)->exists())->toBeTrue()
        ->and(SopAuditLog::query()->where('document_id', $document->id)->where('action', SopAuditLog::ACTION_TRAINING_COMPLETED)->exists())->toBeTrue()
        ->and(SopAuditLog::query()->where('document_id', $document->id)->where('action', SopAuditLog::ACTION_EFFECTIVENESS_RELEASED)->exists())->toBeTrue()
        ->and(SopAuditLog::query()->where('document_id', $document->id)->where('action', SopAuditLog::ACTION_MADE_EFFECTIVE)->exists())->toBeTrue();
});

it('keeps a released document approved until its future effective date arrives', function (): void {
    $this->document->documentType?->update(['requires_training_before_effective' => false]);
    $effectiveDate = now()->addDays(3)->toDateString();

    $released = app(MakeDocumentEffectiveAction::class)->execute(
        $this->document,
        $this->controller,
        $effectiveDate,
    );

    expect($released->documentStatus?->code)->toBe(DocumentStatus::APPROVED)
        ->and($released->effective_date?->toDateString())->toBe($effectiveDate)
        ->and($released->released_for_effectiveness_at)->not->toBeNull();

    app(ActivateDueControlledDocumentsAction::class)->execute();

    expect($released->refresh()->documentStatus?->code)->toBe(DocumentStatus::APPROVED);

    $this->travel(3)->days();

    expect(app(ActivateDueControlledDocumentsAction::class)->execute())->toBe(1)
        ->and($released->refresh()->documentStatus?->code)->toBe(DocumentStatus::EFFECTIVE);
});

it('allows document control to make a form effective without training', function (): void {
    $form = approvedControlledDocument([
        'document_type_id' => DocumentType::query()->where('code', DocumentType::FORM)->firstOrFail()->id,
    ]);

    $released = app(MakeDocumentEffectiveAction::class)->execute(
        $form,
        $this->controller,
        now()->toDateString(),
    );

    expect($released->documentStatus?->code)->toBe(DocumentStatus::EFFECTIVE);
});

it('prevents a trainee from completing someone else\'s assignment', function (): void {
    $assignment = app(AssignDocumentTrainingAction::class)->execute(
        $this->document,
        $this->controller,
        [$this->trainee->id],
    )->firstOrFail();

    expect(fn () => app(CompleteDocumentTrainingAction::class)->execute(
        $assignment,
        $this->controller,
        'Completed on behalf of the trainee.',
    ))->toThrow(ValidationException::class, 'Only the assigned trainee can complete this training.');
});

it('shows make effective on approved documents and activates after training is complete', function (): void {
    $this->actingAs($this->controller);
    Gate::before(static fn (): bool => true);

    Livewire::test(ViewControlledDocument::class, ['record' => $this->document->getRouteKey()])
        ->assertSee('Required training must be completed')
        ->assertActionVisible('makeEffective')
        ->assertActionDisabled('makeEffective');

    $assignment = app(AssignDocumentTrainingAction::class)->execute(
        $this->document,
        $this->controller,
        [$this->controller->id],
    )->firstOrFail();

    app(CompleteDocumentTrainingAction::class)->execute(
        $assignment,
        $this->controller,
        'I have read and understood this procedure.',
    );

    Livewire::test(ViewControlledDocument::class, ['record' => $this->document->refresh()->getRouteKey()])
        ->assertActionEnabled('makeEffective')
        ->callAction('makeEffective', [
            'effective_date' => now()->toDateString(),
            'reason' => 'Training complete.',
        ])
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($this->document->refresh()->documentStatus?->code)->toBe(DocumentStatus::EFFECTIVE);
});

it('lets a document controller assign trainees from the required training table', function (): void {
    $this->actingAs($this->controller);

    Livewire::test(ViewControlledDocument::class, ['record' => $this->document->getRouteKey()])
        ->assertSuccessful()
        ->assertActionVisible('assignTraining');

    Livewire::test(TrainingAssignmentsRelationManager::class, [
        'ownerRecord' => $this->document->load('documentStatus'),
        'pageClass' => ViewControlledDocument::class,
    ])
        ->assertActionVisible(TestAction::make('assignTraining')->table())
        ->callAction(TestAction::make('assignTraining')->table(), [
            'user_ids' => [$this->trainee->id],
        ])
        ->assertNotified();

    expect($this->document->trainingAssignments()->where('user_id', $this->trainee->id)->exists())->toBeTrue();
});

it('hides assign training from users who cannot assign it', function (): void {
    $this->trainee->givePermissionTo([
        'ViewAny:ControlledDocument',
        'View:ControlledDocument',
    ]);
    $this->actingAs($this->trainee);

    Livewire::test(ViewControlledDocument::class, ['record' => $this->document->getRouteKey()])
        ->assertSuccessful()
        ->assertActionHidden('assignTraining');
});

/**
 * @param  array<string, mixed>  $overrides
 */
function approvedControlledDocument(array $overrides = []): ControlledDocument
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
