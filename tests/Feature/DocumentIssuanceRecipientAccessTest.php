<?php

declare(strict_types=1);

use App\Domain\DMS\Services\DocumentIssuanceAccessService;
use App\Domain\DMS\Services\DocumentIssuanceService;
use App\Filament\Resources\DocumentExecutions\Pages\ListDocumentExecutions;
use App\Filament\Resources\DocumentExecutions\Pages\ViewDocumentExecution;
use App\Filament\Resources\DocumentIssuances\Pages\ListDocumentIssuances;
use App\Http\Controllers\ControlledDocumentPrintController;
use App\Http\Controllers\ControlledDocumentViewerController;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentExecution;
use App\Models\DocumentIssuance;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\IssuanceStatus;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms']);
    $this->seed(LookupTableSeeder::class);

    foreach ([
        'ViewAny:DocumentIssuance',
        'View:DocumentIssuance',
        'ViewAny:DocumentExecution',
        'View:DocumentExecution',
        'Update:DocumentExecution',
        'ViewPdf:ControlledDocument',
        'ViewAny:ControlledDocument',
        'View:ControlledDocument',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
});

function recipientAccessDocument(): ControlledDocument
{
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::query()->where('code', DocumentType::LOG)->firstOrFail();
    $documentType->update(['is_issuable' => true, 'requires_sop_reference' => false]);
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create(['document_template_id' => $template]);

    return ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
    ]);
}

it('requires a recipient user or department when issuing a controlled copy', function (): void {
    $document = recipientAccessDocument();
    $issuer = User::factory()->create();

    expect(fn () => app(DocumentIssuanceService::class)->issue($document, $issuer))
        ->toThrow(ValidationException::class, 'Specify the user or department receiving this controlled copy.');
});

it('limits issuance register visibility to assigned recipients', function (): void {
    $document = recipientAccessDocument();
    $issuer = User::factory()->create();
    $recipient = User::factory()->create();
    $otherUser = User::factory()->create();

    $issuance = app(DocumentIssuanceService::class)->issue($document, $issuer, [
        'issued_to_user_id' => $recipient->id,
    ]);

    $recipient->givePermissionTo(['ViewAny:DocumentIssuance', 'View:DocumentIssuance']);
    $otherUser->givePermissionTo(['ViewAny:DocumentIssuance', 'View:DocumentIssuance']);

    $this->actingAs($recipient);
    Livewire::test(ListDocumentIssuances::class)
        ->assertCanSeeTableRecords([$issuance]);

    $this->actingAs($otherUser);
    Livewire::test(ListDocumentIssuances::class)
        ->assertCanNotSeeTableRecords([$issuance]);
});

it('allows department members to access department-issued copies', function (): void {
    $department = Department::factory()->create();
    $document = recipientAccessDocument();
    $issuer = User::factory()->create();
    $departmentMember = User::factory()->create(['department_id' => $department->id]);
    $outsider = User::factory()->create();

    $issuance = app(DocumentIssuanceService::class)->issue($document, $issuer, [
        'issued_to_department_id' => $department->id,
    ]);

    $departmentMember->givePermissionTo(['ViewAny:DocumentIssuance', 'View:DocumentIssuance']);
    $outsider->givePermissionTo(['ViewAny:DocumentIssuance', 'View:DocumentIssuance']);

    expect(app(DocumentIssuanceAccessService::class)->canAccess($departmentMember, $issuance))->toBeTrue()
        ->and(app(DocumentIssuanceAccessService::class)->canAccess($outsider, $issuance))->toBeFalse();
});

it('allows document controllers to access every controlled copy', function (): void {
    $document = recipientAccessDocument();
    $issuer = User::factory()->create();
    $recipient = User::factory()->create();
    $controller = User::factory()->create();

    Permission::findOrCreate('Issue:DocumentIssuance', 'web');
    $controller->givePermissionTo(['ViewAny:DocumentIssuance', 'View:DocumentIssuance', 'Issue:DocumentIssuance']);

    $issuance = app(DocumentIssuanceService::class)->issue($document, $issuer, [
        'issued_to_user_id' => $recipient->id,
    ]);

    expect(app(DocumentIssuanceAccessService::class)->canAccess($controller, $issuance))->toBeTrue();
});

it('scopes execution records to the assigned issuance recipient', function (): void {
    $document = recipientAccessDocument();
    $issuer = User::factory()->create();
    $recipient = User::factory()->create();
    $otherUser = User::factory()->create();

    $execution = app(DocumentIssuanceService::class)->issue($document, $issuer, [
        'issued_to_user_id' => $recipient->id,
    ])->execution;

    $recipient->givePermissionTo([
        'ViewAny:DocumentExecution',
        'View:DocumentExecution',
    ]);
    $otherUser->givePermissionTo([
        'ViewAny:DocumentExecution',
        'View:DocumentExecution',
    ]);

    $this->actingAs($recipient);
    Livewire::test(ListDocumentExecutions::class)
        ->assertCanSeeTableRecords([$execution]);

    $this->actingAs($otherUser);
    Livewire::test(ListDocumentExecutions::class)
        ->assertCanNotSeeTableRecords([$execution]);
});

it('denies controlled-copy viewer access to non-recipients', function (): void {
    $document = recipientAccessDocument();
    $issuer = User::factory()->create();
    $recipient = User::factory()->create();
    $otherUser = User::factory()->create();

    $issuance = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $recipient->id,
        'issued_by' => $issuer->id,
        'issuance_status_id' => IssuanceStatus::idFor(IssuanceStatus::ACTIVE),
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
    ]);

    $otherUser->givePermissionTo([
        'ViewPdf:ControlledDocument',
        'ViewAny:ControlledDocument',
        'View:ControlledDocument',
    ]);

    $this->actingAs($otherUser);

    $request = Request::create("/controlled-documents/{$document->id}/viewer?issuance={$issuance->id}");
    $request->setUserResolver(fn (): User => $otherUser);

    expect(fn () => app(ControlledDocumentViewerController::class)(
        $request,
        $document,
    ))->toThrow(AccessDeniedHttpException::class, 'You do not have access to this controlled copy.');
});

it('denies controlled-copy print access to non-recipients', function (): void {
    $document = recipientAccessDocument();
    $issuer = User::factory()->create();
    $recipient = User::factory()->create();
    $otherUser = User::factory()->create();

    $issuance = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $recipient->id,
        'issued_by' => $issuer->id,
        'issuance_status_id' => IssuanceStatus::idFor(IssuanceStatus::ACTIVE),
        'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
    ]);

    $otherUser->givePermissionTo([
        'ViewPdf:ControlledDocument',
        'ViewAny:ControlledDocument',
        'View:ControlledDocument',
    ]);

    $this->actingAs($otherUser);

    $request = Request::create("/controlled-documents/{$document->id}/pdf-content?issuance={$issuance->id}");
    $request->setUserResolver(fn (): User => $otherUser);

    expect(fn () => app(ControlledDocumentPrintController::class)(
        $request,
        $document,
    ))->toThrow(AccessDeniedHttpException::class, 'You do not have access to this controlled copy.');
});

it('allows QA approvers to open execution records in QA review even when issued to someone else', function (): void {
    $document = recipientAccessDocument();
    $issuer = User::factory()->create();
    $recipient = User::factory()->create();
    $qaApprover = User::factory()->create();

    Permission::findOrCreate('Approve:DocumentExecution', 'web');
    $qaApprover->givePermissionTo(['ViewAny:DocumentExecution', 'View:DocumentExecution', 'Approve:DocumentExecution']);

    $execution = app(DocumentIssuanceService::class)->issue($document, $issuer, [
        'issued_to_user_id' => $recipient->id,
    ])->execution;

    $execution->update(['status' => DocumentExecution::STATUS_QA_REVIEW]);

    $this->actingAs($qaApprover);

    expect(app(DocumentIssuanceAccessService::class)->canAccess($qaApprover, $execution->issuance))->toBeTrue();

    Livewire::test(ListDocumentExecutions::class)
        ->assertCanSeeTableRecords([$execution]);

    Livewire::test(ViewDocumentExecution::class, ['record' => $execution->id])
        ->assertSuccessful();
});

it('allows assigned supervisors to access execution records under review', function (): void {
    $document = recipientAccessDocument();
    $issuer = User::factory()->create();
    $recipient = User::factory()->create();
    $supervisor = User::factory()->create();

    Permission::findOrCreate('Review:DocumentExecution', 'web');
    $supervisor->givePermissionTo(['ViewAny:DocumentExecution', 'View:DocumentExecution', 'Review:DocumentExecution']);

    $execution = app(DocumentIssuanceService::class)->issue($document, $issuer, [
        'issued_to_user_id' => $recipient->id,
        'supervisor_id' => $supervisor->id,
    ])->execution;

    $execution->update(['status' => DocumentExecution::STATUS_UNDER_REVIEW]);

    expect(app(DocumentIssuanceAccessService::class)->canAccess($supervisor, $execution->issuance))->toBeTrue();
});
