<?php

declare(strict_types=1);

use App\Domain\Reporting\Services\OperationalReportCsvExporter;
use App\Enums\ProductModule;
use App\Filament\Pages\Reports\DocumentRegisterReportPage;
use App\Filament\Pages\Reports\GmpExecutionReportPage;
use App\Filament\Pages\Reports\IssuanceRegisterReportPage;
use App\Filament\Pages\Reports\PendingApprovalsReportPage;
use App\Filament\Pages\Reports\PeriodicReviewReportPage;
use App\Filament\Pages\Reports\ReportLibrary;
use App\Filament\Pages\Reports\SopWhereUsedReportPage;
use App\Filament\Support\OperationalReportCatalog;
use App\Models\ControlledDocument;
use App\Models\DocumentExecution;
use App\Models\DocumentIssuance;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    config()->set('modules.enabled', ['dms']);

    foreach ([
        'ViewAny:ControlledDocument',
        'ViewAny:DocumentIssuance',
        'ViewAny:DocumentExecution',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo([
        'ViewAny:ControlledDocument',
        'ViewAny:DocumentIssuance',
        'ViewAny:DocumentExecution',
    ]);
    $this->user->assignRole(Role::findOrCreate('panel_user', 'web'));
    actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('lists only DMS reports while QMS reports are not registered', function (): void {
    $reports = app(OperationalReportCatalog::class)->visible($this->user);

    expect($reports->pluck('key')->all())->toBe([
        'document-register',
        'sop-where-used',
        'periodic-review',
        'pending-approvals',
        'issuance-register',
        'gmp-executions',
    ])->and($reports->every(fn ($report): bool => $report->module === ProductModule::DMS))->toBeTrue();
});

it('hides reports the user cannot view', function (): void {
    $restricted = User::factory()->create();
    $restricted->givePermissionTo('ViewAny:ControlledDocument');

    $keys = app(OperationalReportCatalog::class)->visible($restricted)->pluck('key')->all();

    expect($keys)->toContain('document-register', 'sop-where-used', 'periodic-review', 'pending-approvals')
        ->and($keys)->not->toContain('issuance-register', 'gmp-executions');
});

it('allows the DMS report library and report pages when DMS is enabled', function (): void {
    expect(ReportLibrary::canAccess())->toBeTrue()
        ->and(DocumentRegisterReportPage::canAccess())->toBeTrue()
        ->and(SopWhereUsedReportPage::canAccess())->toBeTrue()
        ->and(PeriodicReviewReportPage::canAccess())->toBeTrue()
        ->and(PendingApprovalsReportPage::canAccess())->toBeTrue()
        ->and(IssuanceRegisterReportPage::canAccess())->toBeTrue()
        ->and(GmpExecutionReportPage::canAccess())->toBeTrue();
});

it('blocks DMS reports when the DMS module is disabled', function (): void {
    config()->set('modules.enabled', []);

    expect(ReportLibrary::canAccess())->toBeFalse()
        ->and(DocumentRegisterReportPage::canAccess())->toBeFalse()
        ->and(SopWhereUsedReportPage::canAccess())->toBeFalse()
        ->and(PeriodicReviewReportPage::canAccess())->toBeFalse()
        ->and(GmpExecutionReportPage::canAccess())->toBeFalse();
});

it('shows controlled documents on the document register', function (): void {
    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->where('code', DocumentType::SOP)->valueOrFail('id'),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
    ]);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_number' => 'SOP-QA-00421',
        'title' => 'Equipment Cleaning Procedure',
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);

    Livewire::test(DocumentRegisterReportPage::class)
        ->assertSuccessful()
        ->assertSee('SOP-QA-00421')
        ->assertSee('Equipment Cleaning Procedure')
        ->assertCanSeeTableRecords([$document]);
});

it('lists overdue and due-soon effective documents on the periodic review report', function (): void {
    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->where('code', DocumentType::SOP)->valueOrFail('id'),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
    ]);

    $overdue = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_number' => 'SOP-QA-OVERDUE',
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'review_date' => now()->subDays(3)->toDateString(),
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);
    $dueSoon = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_number' => 'SOP-QA-DUESOON',
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'review_date' => now()->addDays(10)->toDateString(),
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);
    $later = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_number' => 'SOP-QA-LATER',
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'review_date' => now()->addDays(90)->toDateString(),
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);

    Livewire::test(PeriodicReviewReportPage::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$overdue, $dueSoon])
        ->assertCanNotSeeTableRecords([$later]);
});

it('lists under-review documents on the pending approvals report', function (): void {
    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->where('code', DocumentType::SOP)->valueOrFail('id'),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
    ]);
    $pending = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_number' => 'SOP-QA-PENDING',
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW),
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);
    $effective = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_number' => 'SOP-QA-EFFECTIVE',
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);

    Livewire::test(PendingApprovalsReportPage::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$effective]);
});

it('lists issuances on the issuance register report', function (): void {
    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->where('code', DocumentType::SOP)->valueOrFail('id'),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
    ]);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);
    $issuance = DocumentIssuance::factory()->create([
        'document_id' => $document->getKey(),
        'issuance_number' => 'ISS-QA-0007',
        'issued_by' => $this->user->getKey(),
    ]);

    Livewire::test(IssuanceRegisterReportPage::class)
        ->assertSuccessful()
        ->assertSee('ISS-QA-0007')
        ->assertCanSeeTableRecords([$issuance]);
});

it('lists gmp executions on the executions report', function (): void {
    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->where('code', DocumentType::FORM)->valueOrFail('id'),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
    ]);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);
    $issuance = DocumentIssuance::factory()->create([
        'document_id' => $document->getKey(),
        'issuance_type' => DocumentIssuance::TYPE_EXECUTION,
        'issued_by' => $this->user->getKey(),
    ]);
    $execution = DocumentExecution::factory()->create([
        'document_issuance_id' => $issuance->getKey(),
        'execution_number' => 'REC-QA-0009-C01',
        'document_number' => 'FORM-QA-0012',
        'document_type_code' => DocumentType::FORM,
    ]);

    Livewire::test(GmpExecutionReportPage::class)
        ->assertSuccessful()
        ->assertSee('REC-QA-0009-C01')
        ->assertSee('FORM-QA-0012')
        ->assertCanSeeTableRecords([$execution]);
});

it('lists documents that reference an sop on the where-used report', function (): void {
    $sopTypeId = DocumentType::query()->where('code', DocumentType::SOP)->valueOrFail('id');
    $formTypeId = DocumentType::query()->where('code', DocumentType::FORM)->valueOrFail('id');
    $logTypeId = DocumentType::query()->where('code', DocumentType::LOG)->valueOrFail('id');

    $sopTemplate = DocumentTemplate::factory()->create(['document_type_id' => $sopTypeId]);
    $sopTemplateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $sopTemplate->getKey(),
    ]);
    $formTemplate = DocumentTemplate::factory()->create(['document_type_id' => $formTypeId]);
    $formTemplateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $formTemplate->getKey(),
    ]);
    $logTemplate = DocumentTemplate::factory()->create(['document_type_id' => $logTypeId]);
    $logTemplateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $logTemplate->getKey(),
    ]);

    $sop = ControlledDocument::factory()->create([
        'template_id' => $sopTemplate->getKey(),
        'template_version_id' => $sopTemplateVersion->getKey(),
        'document_type_id' => $sopTypeId,
        'document_number' => 'SOP-QA-WHEREUSED',
        'title' => 'Equipment Cleaning Procedure',
        'version' => 2,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);
    $form = ControlledDocument::factory()->create([
        'template_id' => $formTemplate->getKey(),
        'template_version_id' => $formTemplateVersion->getKey(),
        'document_type_id' => $formTypeId,
        'document_number' => 'FORM-QA-CLEAN',
        'title' => 'Cleaning Record',
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'referenced_controlled_document_id' => $sop->getKey(),
        'referenced_sop_number' => $sop->document_number,
        'referenced_sop_version' => 1,
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);
    $log = ControlledDocument::factory()->create([
        'template_id' => $logTemplate->getKey(),
        'template_version_id' => $logTemplateVersion->getKey(),
        'document_type_id' => $logTypeId,
        'document_number' => 'LOG-QA-CLEAN',
        'title' => 'Cleaning Log',
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
        'referenced_controlled_document_id' => $sop->getKey(),
        'referenced_sop_number' => $sop->document_number,
        'referenced_sop_version' => 2,
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);
    $unreferenced = ControlledDocument::factory()->create([
        'template_id' => $formTemplate->getKey(),
        'template_version_id' => $formTemplateVersion->getKey(),
        'document_type_id' => $formTypeId,
        'document_number' => 'FORM-QA-OTHER',
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);

    Livewire::test(SopWhereUsedReportPage::class)
        ->assertSuccessful()
        ->assertSee('SOP-QA-WHEREUSED')
        ->assertSee('FORM-QA-CLEAN')
        ->assertSee('LOG-QA-CLEAN')
        ->assertCanSeeTableRecords([$form, $log])
        ->assertCanNotSeeTableRecords([$sop, $unreferenced]);
});

it('renders the report library cards for visible DMS reports', function (): void {
    Livewire::test(ReportLibrary::class)
        ->assertSuccessful()
        ->assertSee('Document Register')
        ->assertSee('SOP Where-Used')
        ->assertSee('Periodic Review')
        ->assertSee('GMP Executions');
});

it('downloads operational report csv with a utf8 bom', function (): void {
    $response = app(OperationalReportCsvExporter::class)->download(
        'document-register',
        ['Document #', 'Title'],
        [['SOP-QA-1', 'Cleaning']],
    );

    expect($response)->toBeInstanceOf(StreamedResponse::class)
        ->and($response->headers->get('content-type'))->toContain('text/csv');

    ob_start();
    $response->sendContent();
    $content = (string) ob_get_clean();

    expect($content)->toStartWith("\xEF\xBB\xBF")
        ->toContain('Document #')
        ->toContain('SOP-QA-1');
});
