<?php

declare(strict_types=1);

use App\Actions\Sop\ArchiveDocumentAction;
use App\Actions\Sop\ArchiveTemplateAction;
use App\Actions\Sop\CompleteDocumentRetentionAction;
use App\Actions\Sop\DestroyDocumentAction;
use App\Actions\Sop\MarkDocumentObsoleteAction;
use App\Actions\Sop\MarkTemplateObsoleteAction;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
});

function createEffectiveDocument(): SopDocument
{
    $department = Department::factory()->create(['code' => 'QA']);
    $owner = User::factory()->create(['department_id' => $department->id]);

    $template = SopTemplate::factory()->create([
        'department_id' => $department->id,
        'category_id' => DocumentCategory::factory(),
        'document_type_id' => DocumentType::factory()->create([
            'code' => DocumentType::SOP,
            'requires_sop_reference' => false,
            'is_issuable' => false,
        ])->id,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);

    $version = SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
        'version' => 1,
    ]);

    return SopDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $version->id,
        'department_id' => $department->id,
        'document_type_id' => $template->document_type_id,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
    ]);
}

function createPublishedTemplate(): SopTemplate
{
    $department = Department::factory()->create(['code' => 'QA']);

    $template = SopTemplate::factory()->create([
        'department_id' => $department->id,
        'category_id' => DocumentCategory::factory(),
        'document_type_id' => DocumentType::factory()->create([
            'code' => DocumentType::SOP,
        ])->id,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);

    SopTemplateVersion::factory()->published()->create([
        'sop_template_id' => $template->id,
        'version' => 1,
    ]);

    return $template;
}

it('progresses sop documents through the retention lifecycle', function (): void {
    $user = User::factory()->create();
    $document = createEffectiveDocument();

    app(MarkDocumentObsoleteAction::class)->execute($document, $user, 'Superseded by version 2');
    expect($document->refresh()->documentStatus?->code)->toBe(DocumentStatus::OBSOLETE);

    app(ArchiveDocumentAction::class)->execute($document, $user, 'Moved to archive');
    expect($document->refresh()->documentStatus?->code)->toBe(DocumentStatus::ARCHIVED);

    app(CompleteDocumentRetentionAction::class)->execute($document, $user, 'Retention period met');
    expect($document->refresh()->documentStatus?->code)->toBe(DocumentStatus::RETENTION_COMPLETED);

    app(DestroyDocumentAction::class)->execute($document, $user, 'Physical destruction completed');
    expect($document->refresh()->documentStatus?->code)->toBe(DocumentStatus::DESTROYED);

    expect(SopAuditLog::query()->where('document_id', $document->id)->count())->toBe(4);
});

it('blocks archiving documents that are not obsolete', function (): void {
    $user = User::factory()->create();
    $document = createEffectiveDocument();

    expect(fn () => app(ArchiveDocumentAction::class)->execute($document, $user))
        ->toThrow(ValidationException::class, 'Only obsolete documents can be archived.');
});

it('prevents editing archived documents', function (): void {
    $user = User::factory()->create();
    $document = createEffectiveDocument();

    app(MarkDocumentObsoleteAction::class)->execute($document, $user);
    app(ArchiveDocumentAction::class)->execute($document, $user);

    expect($document->refresh()->canBeEditedBy($user))->toBeFalse();
});

it('marks published templates obsolete before archiving', function (): void {
    $user = User::factory()->create();
    $template = createPublishedTemplate();

    app(MarkTemplateObsoleteAction::class)->execute($template, $user, 'No longer used');

    expect($template->refresh()->templateStatus?->code)->toBe(TemplateStatus::OBSOLETE);
});

it('prevents editing archived templates', function (): void {
    $user = User::factory()->create();
    $template = createPublishedTemplate();

    app(MarkTemplateObsoleteAction::class)->execute($template, $user);
    app(ArchiveTemplateAction::class)->execute($template, $user);

    expect($template->refresh()->canBeEditedBy($user))->toBeFalse();
});
