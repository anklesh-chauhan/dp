<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Enums\DocumentImpactAction;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\ChangeControlDocumentImpact;
use App\Domain\QMS\Services\ApprovedChangeControlDocumentRevisionService;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach ([
        DocumentStatus::DRAFT => 'Draft',
        DocumentStatus::APPROVED => 'Approved',
        DocumentStatus::EFFECTIVE => 'Effective',
        DocumentStatus::OBSOLETE => 'Obsolete',
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

    $this->user = User::factory()->create();
    $this->user->givePermissionTo('Implement:ChangeControl');
    $template = DocumentTemplate::factory()->create();
    $templateVersion = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $template,
    ]);
    $this->source = ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'version' => 2,
    ]);
    $this->changeControl = ChangeControl::factory()->create([
        'status' => ChangeControlStatus::Approved,
        'approved_at' => now(),
    ]);
    $this->impact = ChangeControlDocumentImpact::factory()->create([
        'change_control_id' => $this->changeControl,
        'source_document_id' => $this->source,
        'required_action' => DocumentImpactAction::Revise,
        'rationale' => 'Update the cleaning acceptance criteria.',
    ]);
});

it('creates one traced DMS draft revision from an approved QMS impact', function (): void {
    $service = app(ApprovedChangeControlDocumentRevisionService::class);

    $revision = $service->execute($this->impact, $this->user);
    $retriedRevision = $service->execute($this->impact, $this->user);

    expect($revision->is($retriedRevision))->toBeTrue()
        ->and($revision->supersedes_document_id)->toBe($this->source->id)
        ->and($revision->documentStatus->code)->toBe(DocumentStatus::DRAFT)
        ->and($revision->revision_reason)->toBe(
            "Change Control {$this->changeControl->change_number}: Update the cleaning acceptance criteria.",
        )
        ->and($this->impact->fresh()?->result_document_id)->toBe($revision->id)
        ->and($this->changeControl->fresh()?->status)->toBe(ChangeControlStatus::Implementing)
        ->and($this->changeControl->auditEvents()
            ->where('from_status', ChangeControlStatus::Approved->value)
            ->where('to_status', ChangeControlStatus::Implementing->value)
            ->exists())->toBeTrue()
        ->and(ControlledDocument::query()
            ->where('supersedes_document_id', $this->source->id)
            ->count())->toBe(1);
});

it('requires QMS entitlement before executing a revision', function (): void {
    config()->set('modules.enabled', ['dms']);

    expect(fn () => app(ApprovedChangeControlDocumentRevisionService::class)
        ->execute($this->impact, $this->user))
        ->toThrow(ModuleNotEnabledException::class);

    expect($this->impact->fresh()?->result_document_id)->toBeNull();
});

it('requires implementation permission', function (): void {
    $unauthorizedUser = User::factory()->create();

    expect(fn () => app(ApprovedChangeControlDocumentRevisionService::class)
        ->execute($this->impact, $unauthorizedUser))
        ->toThrow(AuthorizationException::class);

    expect($this->impact->fresh()?->result_document_id)->toBeNull();
});

it('requires an approved change control and a revise impact', function (
    ChangeControlStatus $status,
    DocumentImpactAction $requiredAction,
): void {
    $this->changeControl->update(['status' => $status]);
    $this->impact->update(['required_action' => $requiredAction]);

    expect(fn () => app(ApprovedChangeControlDocumentRevisionService::class)
        ->execute($this->impact, $this->user))
        ->toThrow(ValidationException::class);

    expect($this->impact->fresh()?->result_document_id)->toBeNull();
})->with([
    'unapproved change' => [
        ChangeControlStatus::UnderReview,
        DocumentImpactAction::Revise,
    ],
    'non-revision impact' => [
        ChangeControlStatus::Approved,
        DocumentImpactAction::NoChange,
    ],
]);
