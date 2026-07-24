<?php

declare(strict_types=1);

use App\Actions\Sop\CreateDocumentRevisionAction as LegacyCreateDocumentRevisionAction;
use App\Domain\DMS\Actions\CreateDocumentRevisionAction;
use App\Domain\DMS\Services\DocumentRevisionService;
use App\Models\DocumentStatus;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Services\Sop\DocumentActivationService;
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
    $template = SopTemplate::factory()->create();
    $templateVersion = SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
        'version' => 3,
    ]);
    $source = SopDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'document_number' => 'SOP-QA-00001',
        'version' => 4,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
    ]);
    $source->sections()->create([
        'title' => 'Procedure',
        'section_order' => 1,
        'content' => 'Controlled source content.',
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
        ->and($revision->variables->first()->value)->toBe('Quality Assurance')
        ->and($source->documentStatus->code)->toBe(DocumentStatus::EFFECTIVE);

    expect(SopAuditLog::query()
        ->where('document_id', $revision->id)
        ->where('action', SopAuditLog::ACTION_DOCUMENT_REVISION_CREATED)
        ->exists())->toBeTrue();
});

it('prevents multiple draft revisions in the same document series', function (): void {
    $user = User::factory()->create();
    $template = SopTemplate::factory()->create();
    $templateVersion = SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
    ]);
    $source = SopDocument::factory()->create([
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
    $template = SopTemplate::factory()->create();
    $templateVersion = SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
    ]);
    $source = SopDocument::factory()->create([
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
    $template = SopTemplate::factory()->create();
    $templateVersion = SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
    ]);
    $source = SopDocument::factory()->create([
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
