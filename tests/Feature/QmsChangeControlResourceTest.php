<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Enums\DocumentImpactAction;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\ChangeControlAuditEvent;
use App\Domain\QMS\Models\ChangeControlDocumentImpact;
use App\Filament\Resources\ChangeControls\ChangeControlResource;
use App\Filament\Resources\ChangeControls\Pages\CreateChangeControl;
use App\Filament\Resources\ChangeControls\Pages\EditChangeControl;
use App\Filament\Resources\ChangeControls\Pages\ListChangeControls;
use App\Filament\Resources\ChangeControls\Pages\ViewChangeControl;
use App\Filament\Resources\ChangeControls\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\ChangeControls\RelationManagers\DocumentImpactsRelationManager;
use App\Models\Department;
use App\Models\DocumentStatus;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:ChangeControl',
        'View:ChangeControl',
        'Create:ChangeControl',
        'Update:ChangeControl',
        'Submit:ChangeControl',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('registers the QMS resource only when entitled', function (): void {
    expect(ChangeControlResource::canAccess())->toBeTrue()
        ->and(ChangeControlResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ChangeControlResource::getNavigationGroup())->toBe('QMS · Change Control');

    config()->set('modules.enabled', ['dms']);

    expect(ChangeControlResource::canAccess())->toBeFalse()
        ->and(ChangeControlResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(ChangeControlResource::getUrl())->assertForbidden();
});

it('requires permissions for direct resource access', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListChangeControls::class)
        ->assertForbidden();
});

it('creates an attributed draft with a generated change number', function (): void {
    $department = Department::factory()->create();
    $owner = User::factory()->create();

    Livewire::test(CreateChangeControl::class)
        ->fillForm([
            'title' => 'Replace purified water loop pump',
            'description' => 'Replace the aging circulation pump.',
            'rationale' => 'Reduce reliability and contamination risk.',
            'department_id' => $department->id,
            'owner_id' => $owner->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $changeControl = ChangeControl::query()->sole();

    expect($changeControl->change_number)->toStartWith('CC-')
        ->and($changeControl->status)->toBe(ChangeControlStatus::Draft)
        ->and($changeControl->requested_by)->toBe($this->user->id)
        ->and($changeControl->owner_id)->toBe($owner->id);
});

it('allows draft editing but blocks editing after submission', function (): void {
    $changeControl = ChangeControl::factory()->create();

    expect(ChangeControlResource::canEdit($changeControl))->toBeTrue();

    $changeControl->update(['status' => ChangeControlStatus::Submitted]);

    expect(ChangeControlResource::canEdit($changeControl->fresh()))->toBeFalse();

    Livewire::test(EditChangeControl::class, ['record' => $changeControl->getKey()])
        ->assertForbidden();
});

it('submits a draft through the lifecycle service and shows immutable audit history', function (): void {
    $changeControl = ChangeControl::factory()->create([
        'requested_by' => $this->user,
    ]);

    Livewire::test(ViewChangeControl::class, ['record' => $changeControl->getKey()])
        ->assertSuccessful()
        ->callAction('submit')
        ->assertNotified();

    expect($changeControl->fresh()?->status)->toBe(ChangeControlStatus::Submitted)
        ->and(ChangeControlAuditEvent::query()
            ->where('change_control_id', $changeControl->id)
            ->where('from_status', ChangeControlStatus::Draft->value)
            ->where('to_status', ChangeControlStatus::Submitted->value)
            ->exists())->toBeTrue();

    Livewire::test(AuditEventsRelationManager::class, [
        'ownerRecord' => $changeControl->fresh(),
        'pageClass' => ViewChangeControl::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($changeControl->auditEvents)
        ->assertActionDoesNotExist(TestAction::make('create')->table())
        ->assertActionDoesNotExist(TestAction::make('edit')->table())
        ->assertActionDoesNotExist(TestAction::make('delete')->table());
});

it('routes review and approval actions through attributable lifecycle transitions', function (): void {
    foreach (['Review:ChangeControl', 'Approve:ChangeControl'] as $permission) {
        Permission::findOrCreate($permission, 'web');
        $this->user->givePermissionTo($permission);
    }

    $changeControl = ChangeControl::factory()->create([
        'requested_by' => $this->user,
        'status' => ChangeControlStatus::Submitted,
        'submitted_at' => now(),
    ]);

    Livewire::test(ViewChangeControl::class, ['record' => $changeControl->getKey()])
        ->callAction('beginReview', ['reason' => 'Quality review assigned.'])
        ->assertNotified()
        ->callAction('approve', ['reason' => 'Risk controls are acceptable.'])
        ->assertNotified();

    $events = $changeControl->auditEvents()->orderBy('id')->get();

    expect($changeControl->fresh()?->status)->toBe(ChangeControlStatus::Approved)
        ->and($events)->toHaveCount(2)
        ->and($events->pluck('to_status')->all())->toBe([
            ChangeControlStatus::UnderReview,
            ChangeControlStatus::Approved,
        ])
        ->and($events->pluck('reason')->all())->toBe([
            'Quality review assigned.',
            'Risk controls are acceptable.',
        ]);
});

it('plans document impacts only while the change control is a draft', function (): void {
    DocumentStatus::query()->create(['code' => DocumentStatus::DRAFT, 'name' => 'Draft']);
    TemplateStatus::query()->create(['code' => TemplateStatus::DRAFT, 'name' => 'Draft']);
    $template = SopTemplate::factory()->create();
    $templateVersion = SopTemplateVersion::factory()->create([
        'sop_template_id' => $template,
    ]);
    $source = SopDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
    ]);
    $changeControl = ChangeControl::factory()->create([
        'requested_by' => $this->user,
    ]);

    Livewire::test(DocumentImpactsRelationManager::class, [
        'ownerRecord' => $changeControl,
        'pageClass' => ViewChangeControl::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'required_action' => DocumentImpactAction::Revise->value,
            'source_document_id' => $source->id,
            'rationale' => 'Revise the affected controlled procedure.',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect(ChangeControlDocumentImpact::query()
        ->whereBelongsTo($changeControl)
        ->where('source_document_id', $source->id)
        ->where('required_action', DocumentImpactAction::Revise->value)
        ->exists())->toBeTrue();

    $changeControl->update(['status' => ChangeControlStatus::Submitted]);

    Livewire::test(DocumentImpactsRelationManager::class, [
        'ownerRecord' => $changeControl->fresh(),
        'pageClass' => ViewChangeControl::class,
    ])->assertActionHidden(TestAction::make('create')->table());
});

it('executes an approved revise impact through the traced revision service', function (): void {
    foreach ([
        DocumentStatus::DRAFT => 'Draft',
        DocumentStatus::EFFECTIVE => 'Effective',
    ] as $code => $name) {
        DocumentStatus::query()->create(['code' => $code, 'name' => $name]);
    }
    foreach ([
        TemplateStatus::DRAFT => 'Draft',
        TemplateStatus::PUBLISHED => 'Published',
    ] as $code => $name) {
        TemplateStatus::query()->create(['code' => $code, 'name' => $name]);
    }
    Permission::findOrCreate('Implement:ChangeControl', 'web');
    $this->user->givePermissionTo('Implement:ChangeControl');
    $template = SopTemplate::factory()->create();
    $templateVersion = SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template,
    ]);
    $source = SopDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
    ]);
    $changeControl = ChangeControl::factory()->create([
        'status' => ChangeControlStatus::Approved,
        'approved_at' => now(),
    ]);
    $impact = ChangeControlDocumentImpact::factory()->create([
        'change_control_id' => $changeControl,
        'source_document_id' => $source,
        'required_action' => DocumentImpactAction::Revise,
    ]);

    Livewire::test(DocumentImpactsRelationManager::class, [
        'ownerRecord' => $changeControl,
        'pageClass' => ViewChangeControl::class,
    ])
        ->callAction(TestAction::make('implementRevision')->table($impact))
        ->assertNotified();

    expect($impact->fresh()?->result_document_id)->not->toBeNull()
        ->and($changeControl->fresh()?->status)->toBe(ChangeControlStatus::Implementing)
        ->and(SopDocument::query()
            ->where('supersedes_document_id', $source->id)
            ->count())->toBe(1);
});
