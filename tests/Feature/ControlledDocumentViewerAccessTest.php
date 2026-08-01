<?php

declare(strict_types=1);

use App\Domain\DMS\Services\ControlledDocumentAccessService;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentAccessGrant;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\SopAuditLog;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    foreach ([
        'View:ControlledDocument',
        'ViewPdf:ControlledDocument',
        'PrintPdf:ControlledDocument',
        'DownloadPdf:ControlledDocument',
        'ManagePdfAccess:ControlledDocument',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->firstOrFail()->id,
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->id,
    ]);
    $this->document = ControlledDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
    ]);
    $this->service = app(ControlledDocumentAccessService::class);
});

it('uses role access until a document has explicit sharing grants', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(['View:ControlledDocument', 'ViewPdf:ControlledDocument']);

    expect($this->service->canView($user, $this->document))->toBeTrue();

    ControlledDocumentAccessGrant::factory()->create([
        'controlled_document_id' => $this->document->id,
        'user_id' => User::factory(),
    ]);

    expect($this->service->canView($user, $this->document))->toBeFalse();
});

it('enforces independent view print and download grants', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo([
        'View:ControlledDocument',
        'ViewPdf:ControlledDocument',
        'PrintPdf:ControlledDocument',
        'DownloadPdf:ControlledDocument',
    ]);
    ControlledDocumentAccessGrant::factory()->create([
        'controlled_document_id' => $this->document->id,
        'user_id' => $user->id,
        'can_view' => true,
        'can_print' => false,
        'can_download' => true,
    ]);

    expect($this->service->canView($user, $this->document))->toBeTrue()
        ->and($this->service->canPrint($user, $this->document))->toBeFalse()
        ->and($this->service->canDownload($user, $this->document))->toBeTrue();
});

it('rejects expired document access grants', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(['View:ControlledDocument', 'ViewPdf:ControlledDocument']);
    ControlledDocumentAccessGrant::factory()->create([
        'controlled_document_id' => $this->document->id,
        'user_id' => $user->id,
        'expires_at' => now()->subMinute(),
    ]);

    expect($this->service->canView($user, $this->document))->toBeFalse();
});

it('keeps document owners and access managers in control', function (): void {
    $owner = $this->document->owner;
    $owner->givePermissionTo(['View:ControlledDocument', 'ViewPdf:ControlledDocument']);
    $manager = User::factory()->create();
    $manager->givePermissionTo([
        'View:ControlledDocument',
        'ViewPdf:ControlledDocument',
        'ManagePdfAccess:ControlledDocument',
    ]);
    ControlledDocumentAccessGrant::factory()->create([
        'controlled_document_id' => $this->document->id,
        'user_id' => User::factory(),
    ]);

    expect($this->service->canView($owner, $this->document))->toBeTrue()
        ->and($this->service->canView($manager, $this->document))->toBeTrue()
        ->and($this->service->canManage($manager, $this->document))->toBeTrue();
});

it('renders the custom viewer with permission-based controls', function (): void {
    $view = file_get_contents(resource_path('views/controlled-documents/viewer.blade.php'));
    $javascript = file_get_contents(resource_path('js/pdf-viewer.js'));

    expect($view)
        ->toContain('data-pdf-url')
        ->toContain('@if ($printUrl)')
        ->toContain('@if ($downloadUrl)')
        ->toContain('viewer-watermark')
        ->and($javascript)
        ->toContain("from 'pdfjs-dist'")
        ->toContain('withCredentials: true')
        ->toContain("['p', 's']");
});

it('serves the controlled viewer only to authorized users', function (): void {
    config()->set('modules.enabled', ['dms']);
    $this->document->update([
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::APPROVED),
    ]);
    $authorizedUser = User::factory()->create();
    $authorizedUser->givePermissionTo(['View:ControlledDocument', 'ViewPdf:ControlledDocument']);

    $this->actingAs($authorizedUser)
        ->get(route('controlled-documents.viewer', $this->document))
        ->assertOk()
        ->assertSee('Controlled viewer')
        ->assertDontSee('>Print<', false)
        ->assertDontSee('>Download<', false);

    ControlledDocumentAccessGrant::factory()->create([
        'controlled_document_id' => $this->document->id,
        'user_id' => User::factory(),
    ]);

    $this->actingAs($authorizedUser)
        ->get(route('controlled-documents.viewer', $this->document))
        ->assertForbidden();

    expect(SopAuditLog::query()
        ->where('document_id', $this->document->id)
        ->where('user_id', $authorizedUser->id)
        ->where('action', SopAuditLog::ACTION_PDF_ACCESS_DENIED)
        ->exists())->toBeTrue();
});
