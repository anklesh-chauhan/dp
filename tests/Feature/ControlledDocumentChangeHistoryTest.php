<?php

declare(strict_types=1);

use App\Domain\DMS\Actions\CreateDocumentRevisionAction;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Filament\Resources\ControlledDocuments\Pages\ViewControlledDocument;
use App\Filament\Resources\ControlledDocuments\RelationManagers\ChangeHistoryRelationManager;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\ReportTemplate;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);

    $this->user = User::factory()->create(['name' => 'SOP Maker']);
    actingAs($this->user);
    Gate::before(static fn (): bool => true);

    $this->reportTemplate = ReportTemplate::factory()->create([
        'scope' => ReportScope::ControlledDocument,
        'format' => ReportFormat::Pdf,
        'fields' => app(ReportFieldRegistry::class)->defaultGmpControlledDocumentFields(),
        'created_by' => $this->user->getKey(),
        'updated_by' => $this->user->getKey(),
    ]);
    $this->template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->where('code', DocumentType::SOP)->valueOrFail('id'),
        'report_template_id' => $this->reportTemplate->getKey(),
    ]);
    $this->templateVersion = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $this->template->getKey(),
    ]);
    $this->source = ControlledDocument::factory()->create([
        'template_id' => $this->template->getKey(),
        'template_version_id' => $this->templateVersion->getKey(),
        'department_id' => $this->template->department_id,
        'document_number' => 'SOP-QA-00021',
        'title' => 'Equipment Cleaning Procedure',
        'version' => 1,
        'revision_reason' => null,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'created_by' => $this->user->getKey(),
        'owner_id' => $this->user->getKey(),
    ]);
});

it('exposes change history on the controlled document resource', function (): void {
    expect(ControlledDocumentResource::getRelations())
        ->toContain(ChangeHistoryRelationManager::class);
});

it('lists every version in the document series from the change history table', function (): void {
    $revision = app(CreateDocumentRevisionAction::class)->execute(
        $this->source,
        $this->user,
        'Tighten the rinse-water acceptance limit.',
    );

    Livewire::test(ChangeHistoryRelationManager::class, [
        'ownerRecord' => $revision,
        'pageClass' => ViewControlledDocument::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$this->source->fresh(), $revision])
        ->assertSee('Initial issue')
        ->assertSee('Tighten the rinse-water acceptance limit.')
        ->assertActionDoesNotExist(TestAction::make('create')->table())
        ->assertActionDoesNotExist(TestAction::make('edit')->table($this->source));
});

it('prints change history for the current version and hides later draft revisions', function (): void {
    $revision = app(CreateDocumentRevisionAction::class)->execute(
        $this->source,
        $this->user,
        'Tighten the rinse-water acceptance limit.',
    );

    expect($this->source->fresh()->printableChangeHistory()->pluck('version')->all())
        ->toBe([1])
        ->and($this->source->changeDescription())->toBe('Initial issue')
        ->and($revision->printableChangeHistory()->pluck('version')->all())->toBe([1, 2])
        ->and($revision->changeDescription())->toBe('Tighten the rinse-water acceptance limit.');

    get(route('controlled-documents.draft-preview', $this->source))
        ->assertOk()
        ->assertSee('Change History')
        ->assertSee('Initial issue')
        ->assertDontSee('Tighten the rinse-water acceptance limit.');

    get(route('controlled-documents.draft-preview', $revision))
        ->assertOk()
        ->assertSee('Change History')
        ->assertSee('Initial issue')
        ->assertSee('Tighten the rinse-water acceptance limit.');
});
