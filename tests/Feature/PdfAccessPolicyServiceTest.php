<?php

declare(strict_types=1);

use App\Domain\DMS\Services\ControlledDocumentAccessService;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentAccessGrant;
use App\Models\Department;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\PdfAccessPolicy;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);

    foreach ([
        'View:ControlledDocument',
        'ViewPdf:ControlledDocument',
        'PrintPdf:ControlledDocument',
        'DownloadPdf:ControlledDocument',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->role = Role::findOrCreate('policy test role', 'web');
    $this->user = User::factory()->create();
    $this->user->assignRole($this->role);
    $this->user->givePermissionTo([
        'View:ControlledDocument',
        'ViewPdf:ControlledDocument',
        'PrintPdf:ControlledDocument',
        'DownloadPdf:ControlledDocument',
    ]);
    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->firstOrFail()->id,
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->id,
    ]);
    $this->document = ControlledDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
        'document_type_id' => $template->document_type_id,
    ]);
    $this->service = app(ControlledDocumentAccessService::class);
});

function createPdfPolicyForRole(Role $role, array $attributes = []): PdfAccessPolicy
{
    $policy = PdfAccessPolicy::factory()->create($attributes);
    $policy->roles()->attach($role);

    return $policy;
}

it('uses matching centralized role policies after shield capability checks', function (): void {
    createPdfPolicyForRole($this->role, [
        'can_view' => true,
        'can_print' => false,
        'can_download' => true,
    ]);

    expect($this->service->canView($this->user, $this->document))->toBeTrue()
        ->and($this->service->canPrint($this->user, $this->document))->toBeTrue()
        ->and($this->service->canDownload($this->user, $this->document))->toBeTrue();
});

it('applies deny before allow at an equal priority', function (): void {
    createPdfPolicyForRole($this->role, [
        'effect' => PdfAccessPolicy::EFFECT_ALLOW,
        'priority' => 200,
        'can_print' => true,
    ]);
    createPdfPolicyForRole($this->role, [
        'effect' => PdfAccessPolicy::EFFECT_DENY,
        'priority' => 200,
        'can_print' => true,
    ]);

    expect($this->service->canPrint($this->user, $this->document))->toBeFalse();
});

it('uses the highest priority applicable policy', function (): void {
    createPdfPolicyForRole($this->role, [
        'effect' => PdfAccessPolicy::EFFECT_DENY,
        'priority' => 100,
        'can_view' => true,
    ]);
    createPdfPolicyForRole($this->role, [
        'effect' => PdfAccessPolicy::EFFECT_ALLOW,
        'priority' => 500,
        'can_view' => true,
    ]);

    expect($this->service->canView($this->user, $this->document))->toBeTrue();
});

it('matches department document type status and validity scopes', function (): void {
    createPdfPolicyForRole($this->role, [
        'effect' => PdfAccessPolicy::EFFECT_DENY,
        'priority' => 500,
        'department_id' => Department::factory()->create()->id,
        'document_type_id' => $this->document->document_type_id,
        'document_status_id' => $this->document->document_status_id,
        'effective_from' => now()->subDay(),
        'expires_at' => now()->addDay(),
        'can_view' => true,
    ]);

    expect($this->service->canView($this->user, $this->document))->toBeTrue();

    createPdfPolicyForRole($this->role, [
        'effect' => PdfAccessPolicy::EFFECT_DENY,
        'priority' => 600,
        'department_id' => $this->document->department_id,
        'document_type_id' => $this->document->document_type_id,
        'document_status_id' => $this->document->document_status_id,
        'effective_from' => now()->subDay(),
        'expires_at' => now()->addDay(),
        'can_view' => true,
    ]);

    expect($this->service->canView($this->user, $this->document))->toBeFalse();
});

it('keeps document-specific grants as the exception layer', function (): void {
    createPdfPolicyForRole($this->role, [
        'effect' => PdfAccessPolicy::EFFECT_DENY,
        'priority' => 500,
        'can_view' => true,
        'can_print' => true,
    ]);
    ControlledDocumentAccessGrant::factory()->create([
        'controlled_document_id' => $this->document->id,
        'user_id' => $this->user->id,
        'can_view' => true,
        'can_print' => true,
    ]);

    expect($this->service->canView($this->user, $this->document))->toBeTrue()
        ->and($this->service->canPrint($this->user, $this->document))->toBeTrue();
});

it('never bypasses filament shield capabilities', function (): void {
    createPdfPolicyForRole($this->role, [
        'can_view' => true,
        'can_download' => true,
    ]);
    $this->user->revokePermissionTo('DownloadPdf:ControlledDocument');

    expect($this->service->canDownload($this->user, $this->document))->toBeFalse();
});
