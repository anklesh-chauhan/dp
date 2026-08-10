<?php

declare(strict_types=1);

use App\Actions\Sop\CreateDocumentRevisionAction as LegacyCreateDocumentRevisionAction;
use App\Domain\DMS\Actions\CreateDocumentRevisionAction;
use App\Domain\DMS\Services\DocumentActivationService;
use App\Domain\DMS\Services\DocumentRevisionService;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use App\Models\ControlledDocumentSectionItem;
use App\Models\ControlledDocumentSectionTable;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\SopAuditLog;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Services\Sop\DocumentRevisionService as LegacyDocumentRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
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
});

it('resolves legacy revision entry points through the DMS domain boundary', function (): void {
    expect(app(LegacyCreateDocumentRevisionAction::class))
        ->toBeInstanceOf(CreateDocumentRevisionAction::class)
        ->and(app(LegacyDocumentRevisionService::class))
        ->toBeInstanceOf(DocumentRevisionService::class);
});

it('creates an independent draft document version pinned to the same template version', function (): void {
    $user = User::factory()->create();
    $template = DocumentTemplate::factory()->create();
    $templateVersion = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $template->id,
        'version' => 3,
    ]);
    $source = ControlledDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'document_number' => 'SOP-QA-00001',
        'version' => 4,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
    ]);
    $sourceSection = $source->sections()->create([
        'title' => 'Procedure',
        'section_order' => 1,
        'section_type' => ControlledDocumentSection::TYPE_CHECKLIST,
        'content' => 'Controlled source content.',
    ]);
    $sourceTable = ControlledDocumentSectionTable::factory()->create([
        'section_id' => $sourceSection,
        'title' => 'Line clearance checks',
        'execution_layout' => 'table',
        'row_count' => 3,
    ]);
    $sourceTable->items()->create([
        'item_order' => 1,
        'label' => 'Verify line clearance',
        'value_type' => ControlledDocumentSectionItem::VALUE_BOOLEAN,
        'is_required' => true,
    ]);
    $source->variables()->create([
        'variable_name' => 'department',
        'value' => 'Quality Assurance',
    ]);

    $revision = app(CreateDocumentRevisionAction::class)->execute(
        $source,
        $user,
        'Update the cleaning acceptance criteria.',
    );

    expect($revision->id)->not->toBe($source->id)
        ->and($revision->document_series_id)->toBe($source->refresh()->document_series_id)
        ->and($revision->supersedes_document_id)->toBe($source->id)
        ->and($revision->document_number)->toBe($source->document_number)
        ->and($revision->version)->toBe(5)
        ->and($revision->template_version_id)->toBe($templateVersion->id)
        ->and($revision->documentStatus->code)->toBe(DocumentStatus::DRAFT)
        ->and($revision->revision_reason)->toBe('Update the cleaning acceptance criteria.')
        ->and($revision->sections->first()->content)->toBe('Controlled source content.')
        ->and($revision->sections->first()->items)->toHaveCount(1)
        ->and($revision->sections->first()->items->first()->label)->toBe('Verify line clearance')
        ->and($revision->sections->first()->executionTables)->toHaveCount(1)
        ->and($revision->sections->first()->executionTables->first()->title)->toBe('Line clearance checks')
        ->and($revision->sections->first()->executionTables->first()->row_count)->toBe(3)
        ->and($revision->sections->first()->executionTables->first()->items)->toHaveCount(1)
        ->and($revision->variables->first()->value)->toBe('Quality Assurance')
        ->and($source->documentStatus->code)->toBe(DocumentStatus::EFFECTIVE);

    expect(SopAuditLog::query()
        ->where('document_id', $revision->id)
        ->where('action', SopAuditLog::ACTION_DOCUMENT_REVISION_CREATED)
        ->exists())->toBeTrue();
});

it('prevents multiple draft revisions in the same document series', function (): void {
    $user = User::factory()->create();
    $template = DocumentTemplate::factory()->create();
    $templateVersion = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $template->id,
    ]);
    $source = ControlledDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'department_id' => $template->department_id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
    ]);

    app(CreateDocumentRevisionAction::class)->execute($source, $user, 'First controlled revision.');

    expect(fn () => app(CreateDocumentRevisionAction::class)->execute($source, $user, 'Duplicate revision.'))
        ->toThrow(ValidationException::class);
});

it('does not revise a draft document', function (): void {
    $template = DocumentTemplate::factory()->create();
    $templateVersion = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $template->id,
    ]);
    $source = ControlledDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'department_id' => $template->department_id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
    ]);

    expect(fn () => app(CreateDocumentRevisionAction::class)->execute(
        $source,
        User::factory()->create(),
        'Not allowed.',
    ))->toThrow(ValidationException::class);
});

it('supersedes the prior effective version only when its revision becomes effective', function (): void {
    $user = User::factory()->create();
    $template = DocumentTemplate::factory()->create();
    $templateVersion = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $template->id,
    ]);
    $source = ControlledDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'department_id' => $template->department_id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'version' => 1,
    ]);
    $revision = app(CreateDocumentRevisionAction::class)->execute(
        $source,
        $user,
        'Introduce the approved replacement procedure.',
    );

    expect($source->refresh()->documentStatus->code)->toBe(DocumentStatus::EFFECTIVE)
        ->and($revision->documentStatus->code)->toBe(DocumentStatus::DRAFT);

    app(DocumentActivationService::class)->activate($revision, $user);

    expect($source->refresh()->documentStatus->code)->toBe(DocumentStatus::SUPERSEDED)
        ->and($revision->refresh()->documentStatus->code)->toBe(DocumentStatus::EFFECTIVE);

    expect(SopAuditLog::query()
        ->where('document_id', $source->id)
        ->where('action', SopAuditLog::ACTION_SUPERSEDED)
        ->where('new_values->superseded_by_document_id', $revision->id)
        ->exists())->toBeTrue();
});
