<?php

declare(strict_types=1);

use App\Data\ControlledDocumentData;
use App\Domain\DMS\Actions\CreateDocumentFromTemplateAction;
use App\Domain\DMS\Contracts\ControlledDocumentPdfRenderer;
use App\Domain\DMS\Services\VariableResolverService;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\Organization;
use App\Models\TemplateStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach ([
        'ViewAny:Organization',
        'View:Organization',
        'Create:Organization',
        'Update:Organization',
        'View:ControlledDocument',
        'ViewPdf:ControlledDocument',
        'PrintPdf:ControlledDocument',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo([
        'ViewAny:Organization',
        'View:Organization',
        'Create:Organization',
        'Update:Organization',
        'View:ControlledDocument',
        'ViewPdf:ControlledDocument',
        'PrintPdf:ControlledDocument',
    ]);
    $this->actingAs($this->user);
});

it('creates an attributed organization and makes the first organization the default', function (): void {
    Livewire::test(CreateOrganization::class)
        ->fillForm([
            'code' => 'client-001',
            'legal_name' => 'Acme Pharmaceuticals Private Limited',
            'display_name' => 'Acme Pharma',
            'registration_number' => 'CIN-U24230GJ2026PTC001',
            'tax_identifier' => 'GSTIN-24AAAAA0000A1Z5',
            'regulatory_identifiers' => [
                'manufacturing_licence' => 'G/25/2026/001',
            ],
            'address_line_1' => 'Plot 10, Pharma Industrial Estate',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'postal_code' => '380001',
            'country_code' => 'in',
            'email' => 'quality@acme.example',
            'timezone' => 'Asia/Kolkata',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $organization = Organization::query()->sole();

    expect($organization->code)->toBe('CLIENT-001')
        ->and($organization->country_code)->toBe('IN')
        ->and($organization->is_default)->toBeTrue()
        ->and($organization->created_by)->toBe($this->user->id);
});

it('enforces one organization profile per deployment', function (): void {
    $organization = Organization::factory()->create();

    expect(OrganizationResource::canCreate())->toBeFalse()
        ->and(Organization::defaultActive()?->is($organization))->toBeTrue()
        ->and(fn () => Organization::factory()->create())
        ->toThrow(ValidationException::class, 'already has an Organization Profile');
});

it('renders documents from their immutable organization snapshot', function (): void {
    Storage::fake('local');
    $organization = Organization::factory()->create([
        'legal_name' => 'Snapshot Pharma Limited',
        'display_name' => 'Snapshot Pharma',
        'address_line_1' => 'Original Registered Address',
        'city' => 'Mumbai',
        'registration_number' => 'REG-ORIGINAL',
        'document_header' => 'Controlled quality document',
        'document_footer' => 'Confidential',
    ]);
    DocumentStatus::query()->create([
        'code' => DocumentStatus::DRAFT,
        'name' => 'Draft',
    ]);
    TemplateStatus::query()->create([
        'code' => TemplateStatus::DRAFT,
        'name' => 'Draft',
    ]);
    $approvedStatus = DocumentStatus::query()->create([
        'code' => DocumentStatus::APPROVED,
        'name' => 'Approved',
    ]);
    $template = DocumentTemplate::factory()->create();
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
    ]);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'organization_id' => $organization,
        'organization_snapshot' => $organization->identitySnapshot(),
        'document_status_id' => $approvedStatus,
    ]);

    $organization->update([
        'legal_name' => 'Renamed Pharma Limited',
        'address_line_1' => 'New Address',
    ]);

    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('render')
        ->once()
        ->with(
            Mockery::on(fn (ControlledDocument $renderedDocument): bool => $renderedDocument->is($document)),
            Mockery::any(),
            null,
            Mockery::on(fn (array $identity): bool => $identity['legal_name'] === 'Snapshot Pharma Limited'
                && $identity['address_line_1'] === 'Original Registered Address'
                && $identity['registration_number'] === 'REG-ORIGINAL'
                && $identity['document_header'] === 'Controlled quality document'
                && $identity['document_footer'] === 'Confidential'),
        )
        ->andReturn('%PDF-1.4 snapshot-organization');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $this->get(route('controlled-documents.print', $document))
        ->assertOk()
        ->assertStreamed()
        ->assertHeader('content-type', 'application/pdf');
});

it('falls back to the linked organization when an older document has no snapshot', function (): void {
    Storage::fake('local');
    $organization = Organization::factory()->create([
        'legal_name' => 'Legacy Document Pharma Limited',
        'registration_number' => 'REG-LEGACY',
        'logo_path' => 'organization-logos/legacy-logo.png',
    ]);
    DocumentStatus::query()->create([
        'code' => DocumentStatus::DRAFT,
        'name' => 'Draft',
    ]);
    TemplateStatus::query()->create([
        'code' => TemplateStatus::DRAFT,
        'name' => 'Draft',
    ]);
    $approvedStatus = DocumentStatus::query()->create([
        'code' => DocumentStatus::APPROVED,
        'name' => 'Approved',
    ]);
    $template = DocumentTemplate::factory()->create();
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
    ]);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'organization_id' => $organization,
        'organization_snapshot' => null,
        'document_status_id' => $approvedStatus,
    ]);

    $renderer = Mockery::mock(ControlledDocumentPdfRenderer::class);
    $renderer->shouldReceive('render')
        ->once()
        ->with(
            Mockery::on(fn (ControlledDocument $renderedDocument): bool => $renderedDocument->is($document)),
            Mockery::any(),
            null,
            Mockery::on(fn (array $identity): bool => $identity['legal_name'] === 'Legacy Document Pharma Limited'
                && $identity['registration_number'] === 'REG-LEGACY'
                && $identity['logo_path'] === 'organization-logos/legacy-logo.png'),
        )
        ->andReturn('%PDF-1.4 linked-organization');
    app()->instance(ControlledDocumentPdfRenderer::class, $renderer);

    $this->get(route('controlled-documents.print', $document))
        ->assertOk()
        ->assertStreamed()
        ->assertHeader('content-type', 'application/pdf');
});

it('resolves organization placeholders without duplicating details in every template', function (): void {
    $organization = Organization::factory()->create([
        'legal_name' => 'Reusable Client Limited',
        'registration_number' => 'REG-REUSABLE',
    ]);

    $rendered = app(VariableResolverService::class)->replace(
        '{{ organization.legal_name }} | {{ organization.registration_number }}',
        $organization->templateVariables(),
    );

    expect($rendered)->toBe('Reusable Client Limited | REG-REUSABLE');
});

it('automatically snapshots the deployment profile when generating a document', function (): void {
    $organization = Organization::factory()->create([
        'legal_name' => 'Deployment Profile Limited',
        'registration_number' => 'REG-DEPLOYMENT',
    ]);
    DocumentStatus::query()->create(['code' => DocumentStatus::DRAFT, 'name' => 'Draft']);
    TemplateStatus::query()->create(['code' => TemplateStatus::DRAFT, 'name' => 'Draft']);
    $publishedTemplateStatus = TemplateStatus::query()->create(['code' => TemplateStatus::PUBLISHED, 'name' => 'Published']);
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::factory()->create();
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'template_status_id' => $publishedTemplateStatus,
        'current_version' => 1,
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $template,
        'template_status_id' => $publishedTemplateStatus,
    ]);
    $templateVersion->sections()->create([
        'title' => 'Organization',
        'section_order' => 1,
        'content' => '{{ organization.legal_name }} / {{ organization.registration_number }}',
    ]);

    $document = app(CreateDocumentFromTemplateAction::class)->execute(new ControlledDocumentData(
        templateId: $template->id,
        title: 'Automatically Branded Document',
        ownerId: $this->user->id,
        createdBy: $this->user->id,
        templateVersionId: $templateVersion->id,
        documentNumber: 'SOP-QA-ORG-001',
    ));

    expect($document->organization_id)->toBe($organization->id)
        ->and($document->organization_snapshot['legal_name'])->toBe('Deployment Profile Limited')
        ->and($document->sections->sole()->content)->toBe('Deployment Profile Limited / REG-DEPLOYMENT');
});
