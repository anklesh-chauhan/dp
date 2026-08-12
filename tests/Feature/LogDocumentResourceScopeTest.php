<?php

declare(strict_types=1);

use App\Filament\Resources\LogDocuments\LogDocumentResource;
use App\Filament\Resources\LogDocuments\Pages\ListLogDocuments;
use App\Filament\Resources\LogDocuments\Pages\ViewLogDocument;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentIssuance;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
});

function logDocumentResourceDocument(string $documentTypeCode): ControlledDocument
{
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::query()->where('code', $documentTypeCode)->firstOrFail();
    $documentType->update(['is_issuable' => true, 'requires_sop_reference' => false]);
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
    ]);

    return ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'document_number' => $documentTypeCode.'-QA-SCOPE-00001',
    ]);
}

it('limits the issuable documents resource to issuable masters', function (): void {
    $log = logDocumentResourceDocument(DocumentType::LOG);
    $nonIssuable = logDocumentResourceDocument(DocumentType::SOP);
    $nonIssuable->documentType->update(['is_issuable' => false]);

    $ids = LogDocumentResource::getEloquentQuery()->pluck('id');

    expect(LogDocumentResource::getNavigationLabel())->toBe('Issuable Documents')
        ->and(LogDocumentResource::getModelLabel())->toBe('Issuable Document')
        ->and(LogDocumentResource::getPluralModelLabel())->toBe('Issuable Documents')
        ->and($ids)->toContain($log->id)
        ->and($ids)->not->toContain($nonIssuable->id)
        ->and(ControlledDocument::query()->logDocuments()->pluck('id'))->toContain($log->id)
        ->and(ControlledDocument::query()->logDocuments()->pluck('id'))->not->toContain($nonIssuable->id);
});

it('does not resolve non-issuable masters on the issuable documents view page', function (): void {
    Gate::before(static fn (): bool => true);

    $nonIssuable = logDocumentResourceDocument(DocumentType::SOP);
    $nonIssuable->documentType->update(['is_issuable' => false]);
    $this->actingAs(User::factory()->create());

    expect(fn () => Livewire::test(ViewLogDocument::class, ['record' => $nonIssuable->getRouteKey()]))
        ->toThrow(ModelNotFoundException::class);
});

it('issues a controlled copy from the issuable documents list', function (): void {
    config()->set('modules.enabled', ['dms']);

    foreach (['ViewAny:LogDocument', 'View:LogDocument', 'Issue:DocumentIssuance'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['ViewAny:LogDocument', 'View:LogDocument', 'Issue:DocumentIssuance']);
    $this->actingAs($user);

    $document = logDocumentResourceDocument(DocumentType::FORM);
    $recipient = User::factory()->create();

    Livewire::test(ListLogDocuments::class)
        ->assertCanSeeTableRecords([$document])
        ->assertActionVisible(TestAction::make('issueControlledCopy')->table($document))
        ->callAction(TestAction::make('issueControlledCopy')->table($document), [
            'issuance_type' => DocumentIssuance::TYPE_EXECUTION,
            'issued_to_user_id' => $recipient->id,
        ])
        ->assertNotified();

    expect(DocumentIssuance::query()
        ->where('document_id', $document->id)
        ->where('issued_to_user_id', $recipient->id)
        ->exists())->toBeTrue();
});

it('hides the list issue action without issuance permission', function (): void {
    config()->set('modules.enabled', ['dms']);

    foreach (['ViewAny:LogDocument', 'View:LogDocument'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['ViewAny:LogDocument', 'View:LogDocument']);
    $this->actingAs($user);

    $document = logDocumentResourceDocument(DocumentType::FORM);

    Livewire::test(ListLogDocuments::class)
        ->assertCanSeeTableRecords([$document])
        ->assertActionHidden(TestAction::make('issueControlledCopy')->table($document));
});
