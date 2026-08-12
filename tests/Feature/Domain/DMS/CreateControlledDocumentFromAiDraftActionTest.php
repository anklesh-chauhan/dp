<?php

declare(strict_types=1);

use App\Models\ControlledDocument;
use App\Models\ControlledDocumentDraftSession;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateSection;
use App\Models\DocumentTemplateVariable;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\Organization;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Models\VariableDataType;
use App\Services\AI\Actions\CreateControlledDocumentFromAiDraftAction;
use App\Services\AI\Enums\ControlledDocumentDraftSessionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    config()->set('modules.enabled', ['dms', 'ai']);
    Gate::before(static fn (): bool => true);

    TemplateStatus::query()->create(['code' => TemplateStatus::DRAFT, 'name' => 'Draft']);
    TemplateStatus::query()->create(['code' => TemplateStatus::PUBLISHED, 'name' => 'Published']);
    DocumentStatus::query()->create(['code' => DocumentStatus::DRAFT, 'name' => 'Draft']);
    VariableDataType::query()->create(['code' => VariableDataType::TEXT, 'name' => 'Text']);
    Organization::factory()->create(['is_default' => true, 'is_active' => true]);

    $this->creator = User::factory()->create();
    $this->owner = User::factory()->create();
    $department = Department::factory()->create();
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => DocumentCategory::factory(),
        'document_type_id' => DocumentType::factory()->state(['code' => DocumentType::POLICY]),
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);
    $version = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $template,
        'version' => 1,
    ]);
    DocumentTemplateVariable::factory()->create([
        'template_version_id' => $version,
        'name' => 'purpose',
        'label' => 'Purpose',
        'required' => true,
        'default_value' => null,
    ]);
    DocumentTemplateSection::factory()->create([
        'template_version_id' => $version,
        'title' => 'Purpose',
        'section_order' => 1,
        'content' => '{{purpose}}',
    ]);

    $this->session = ControlledDocumentDraftSession::factory()->create([
        'created_by' => $this->creator,
        'owner_id' => $this->owner,
        'template_id' => $template,
        'template_version_id' => $version,
        'status' => ControlledDocumentDraftSessionStatus::PREVIEW_READY,
        'title' => 'Change Control Policy',
        'brief' => ['purpose' => 'Define change controls.'],
        'draft_variables' => ['purpose' => 'Define change controls.'],
        'preview_revision' => 1,
    ]);
    $this->session->forceFill([
        'preview_hash' => $this->session->calculatePreviewHash(),
    ])->save();
});

it('creates exactly one draft through the canonical template action', function (): void {
    $action = app(CreateControlledDocumentFromAiDraftAction::class);
    $document = $action->execute(
        $this->session,
        $this->creator,
        $this->session->preview_hash,
    );
    $repeated = $action->execute(
        $this->session->refresh(),
        $this->creator,
        $this->session->preview_hash,
    );

    expect($document->is($repeated))->toBeTrue()
        ->and(ControlledDocument::query()->count())->toBe(1)
        ->and($document->documentStatus->code)->toBe(DocumentStatus::DRAFT)
        ->and($document->sections->first()->content)->toBe('Define change controls.')
        ->and($this->session->refresh()->status)->toBe(ControlledDocumentDraftSessionStatus::CONFIRMED)
        ->and($this->session->controlled_document_id)->toBe($document->id);
});

it('rejects confirmation when the preview hash is stale', function (): void {
    expect(fn () => app(CreateControlledDocumentFromAiDraftAction::class)->execute(
        $this->session,
        $this->creator,
        str_repeat('0', 64),
    ))->toThrow(ValidationException::class, 'preview changed')
        ->and(ControlledDocument::query()->count())->toBe(0);
});
