<?php

declare(strict_types=1);

use App\Domain\DMS\Actions\AddSectionReviewCommentAction;
use App\Domain\DMS\Actions\ResolveSectionReviewCommentAction;
use App\Exceptions\WorkflowException;
use App\Filament\Resources\ControlledDocuments\Pages\ViewControlledDocument;
use App\Filament\Resources\ControlledDocuments\RelationManagers\DocumentSectionRelationManager;
use App\Filament\Resources\ControlledDocuments\RelationManagers\SectionReviewCommentsRelationManager;
use App\Models\ApprovalDecision;
use App\Models\ApprovalStepType;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use App\Models\ControlledDocumentSectionReviewComment;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\SopApproval;
use App\Models\SopAuditLog;
use App\Models\SopWorkflow;
use App\Models\SopWorkflowStep;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    config()->set('modules.enabled', ['dms']);

    foreach ([
        'Approve:ControlledDocument',
        'Approve:SopApproval',
        'View:ControlledDocument',
        'Update:ControlledDocument',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->department = Department::factory()->create();
    $this->reviewerRole = Role::findOrCreate('section review reviewer', 'web');
    $this->author = User::factory()->create(['department_id' => $this->department]);
    $this->reviewer = User::factory()->create(['department_id' => $this->department]);
    $this->reviewer->assignRole([
        $this->reviewerRole,
        Role::findOrCreate('panel_user', 'web'),
    ]);
    $this->reviewer->givePermissionTo([
        'Approve:ControlledDocument',
        'Approve:SopApproval',
        'View:ControlledDocument',
    ]);

    $documentType = DocumentType::query()->where('code', DocumentType::SOP)->firstOrFail();
    $category = DocumentCategory::factory()->create();
    $this->template = DocumentTemplate::factory()->create([
        'category_id' => $category,
        'department_id' => $this->department,
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'created_by' => $this->author,
    ]);
    $this->templateVersion = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $this->template,
        'created_by' => $this->author,
    ]);
    $this->workflow = SopWorkflow::factory()->create([
        'department_id' => $this->department,
        'is_active' => true,
    ]);
    $this->step = SopWorkflowStep::factory()->create([
        'workflow_id' => $this->workflow,
        'step_no' => 1,
        'role_id' => $this->reviewerRole,
        'department_id' => $this->department,
        'approval_step_type_id' => ApprovalStepType::idFor(ApprovalStepType::CHECKER),
        'is_mandatory' => true,
    ]);
    $this->document = ControlledDocument::factory()->create([
        'template_id' => $this->template,
        'template_version_id' => $this->templateVersion,
        'department_id' => $this->department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW),
        'created_by' => $this->author,
        'owner_id' => $this->author,
    ]);
    $this->section = ControlledDocumentSection::factory()->create([
        'document_id' => $this->document,
        'title' => 'Purpose',
        'section_order' => 1,
        'content' => '<p>Clean equipment with purified water.</p>',
    ]);
    $this->documentApproval = SopApproval::factory()->create([
        'document_id' => $this->document,
        'workflow_step_id' => $this->step,
        'approved_by' => null,
        'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
        'approved_at' => null,
        'signature_hash' => null,
    ]);

    Gate::before(static fn (): bool => true);
});

it('lets the current reviewer view sections and add a comment during approval', function (): void {
    $this->actingAs($this->reviewer);

    Livewire::test(DocumentSectionRelationManager::class, [
        'ownerRecord' => $this->document,
        'pageClass' => ViewControlledDocument::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$this->section])
        ->assertActionVisible(TestAction::make('view')->table($this->section))
        ->assertActionHidden(TestAction::make('edit')->table($this->section))
        ->assertActionVisible(TestAction::make('addReviewComment')->table($this->section))
        ->callAction(TestAction::make('addReviewComment')->table($this->section), [
            'body' => 'Change the rinse water to WFI and name the acceptance limit.',
        ])
        ->assertNotified();

    $comment = ControlledDocumentSectionReviewComment::query()->first();

    expect($comment)->not->toBeNull()
        ->and($comment->section_id)->toBe($this->section->id)
        ->and($comment->document_id)->toBe($this->document->id)
        ->and($comment->author_id)->toBe($this->reviewer->id)
        ->and($comment->sop_approval_id)->toBe($this->documentApproval->id)
        ->and($comment->body)->toBe('Change the rinse water to WFI and name the acceptance limit.')
        ->and($comment->isOpen())->toBeTrue();

    expect(SopAuditLog::query()->where('action', SopAuditLog::ACTION_SECTION_REVIEW_COMMENTED)->exists())->toBeTrue();
});

it('shows reviewer attention to the maker on the document view and comments table', function (): void {
    ControlledDocumentSectionReviewComment::factory()
        ->forSection($this->section)
        ->forApproval($this->documentApproval)
        ->create([
            'author_id' => $this->reviewer,
            'body' => 'Replace purified water with WFI in this section.',
        ]);

    $this->actingAs($this->author);

    Livewire::test(ViewControlledDocument::class, ['record' => $this->document->getRouteKey()])
        ->assertSee('Reviewer attention')
        ->assertSee('Purpose');

    Livewire::test(SectionReviewCommentsRelationManager::class, [
        'ownerRecord' => $this->document,
        'pageClass' => ViewControlledDocument::class,
    ])
        ->assertSuccessful()
        ->assertSee('Purpose')
        ->assertSee('Replace purified water with WFI in this section.')
        ->assertSee('Needs update')
        ->assertActionHidden(TestAction::make('resolve')->table(
            $this->document->sectionReviewComments()->first()
        ));

    Livewire::test(DocumentSectionRelationManager::class, [
        'ownerRecord' => $this->document,
        'pageClass' => ViewControlledDocument::class,
    ])
        ->assertSee('1 open')
        ->assertActionVisible(TestAction::make('view')->table($this->section))
        ->assertActionHidden(TestAction::make('addReviewComment')->table($this->section))
        ->assertActionHidden(TestAction::make('edit')->table($this->section));
});

it('lets the maker mark a section comment as addressed after the document returns to draft', function (): void {
    $comment = ControlledDocumentSectionReviewComment::factory()
        ->forSection($this->section)
        ->forApproval($this->documentApproval)
        ->create([
            'author_id' => $this->reviewer,
            'body' => 'Add the equipment ID to the purpose.',
        ]);

    $this->document->update([
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
    ]);

    $this->actingAs($this->author);

    Livewire::test(SectionReviewCommentsRelationManager::class, [
        'ownerRecord' => $this->document->fresh(['documentStatus']),
        'pageClass' => ViewControlledDocument::class,
    ])
        ->assertActionVisible(TestAction::make('resolve')->table($comment))
        ->callAction(TestAction::make('resolve')->table($comment))
        ->assertNotified();

    expect($comment->refresh()->isResolved())->toBeTrue()
        ->and($comment->resolved_by)->toBe($this->author->id);

    expect(SopAuditLog::query()->where('action', SopAuditLog::ACTION_SECTION_REVIEW_COMMENT_RESOLVED)->exists())->toBeTrue();
});

it('does not let the maker comment during approval or the reviewer resolve comments', function (): void {
    $this->actingAs($this->author);

    expect(fn () => app(AddSectionReviewCommentAction::class)->execute(
        $this->section,
        $this->author,
        'Please ignore this.',
    ))->toThrow(WorkflowException::class);

    $comment = app(AddSectionReviewCommentAction::class)->execute(
        $this->section,
        $this->reviewer,
        'Tighten the rinse instruction.',
    );

    expect(fn () => app(ResolveSectionReviewCommentAction::class)->execute(
        $comment,
        $this->reviewer,
    ))->toThrow(WorkflowException::class);

    $this->document->update([
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
    ]);

    expect(fn () => app(AddSectionReviewCommentAction::class)->execute(
        $this->section->fresh(['document.documentStatus']),
        $this->reviewer,
        'Another comment after return.',
    ))->toThrow(WorkflowException::class);
});
