<?php

declare(strict_types=1);

use App\Domain\DMS\Actions\CreateTemplateDraftRevisionAction;
use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Filament\Resources\DocumentTemplates\Pages\EditDocumentTemplate;
use App\Filament\Resources\DocumentTemplates\Pages\ViewDocumentTemplate;
use App\Filament\Resources\DocumentTemplates\RelationManagers\SectionRelationManager;
use App\Jobs\CompleteTemplateSectionWithAiJob;
use App\Jobs\GenerateTemplateSectionTitlesJob;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateSection;
use App\Models\DocumentTemplateVariable;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Services\AI\Contracts\TemplateGenerator;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    config(['modules.enabled' => ['dms', 'ai']]);
    Gate::before(static fn (): bool => true);
});

it('locks published document templates from editing and section ai actions', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $template = DocumentTemplate::factory()->create([
        'created_by' => $user->getKey(),
        'document_type_id' => DocumentType::query()->firstOrFail(),
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
    ]);
    $version = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'version' => 1,
    ]);
    DocumentTemplateSection::factory()->create([
        'template_version_id' => $version->getKey(),
        'title' => 'Purpose',
    ]);

    expect($template->isEditable())->toBeFalse()
        ->and($template->canBeEditedBy($user))->toBeFalse()
        ->and($version->isContentEditable())->toBeFalse()
        ->and($template->canStartDraftRevisionBy($user))->toBeTrue();

    Livewire::test(EditDocumentTemplate::class, ['record' => $template->getKey()])
        ->assertRedirect(ViewDocumentTemplate::getUrl(['record' => $template]));

    Livewire::test(SectionRelationManager::class, [
        'ownerRecord' => $template,
        'pageClass' => EditDocumentTemplate::class,
    ])
        ->assertActionHidden(TestAction::make('create')->table())
        ->assertActionHidden(TestAction::make('generateSectionTitlesWithAi')->table())
        ->assertActionHidden(TestAction::make('edit')->table($template->sections()->first()))
        ->assertActionHidden(TestAction::make('completeWithAi')->table($template->sections()->first()))
        ->assertActionHidden(TestAction::make('delete')->table($template->sections()->first()));
});

it('does not let ai jobs mutate published template sections', function (): void {
    $user = User::factory()->create();
    $template = DocumentTemplate::factory()->create([
        'created_by' => $user->getKey(),
        'document_type_id' => DocumentType::query()->firstOrFail(),
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
    ]);
    $version = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'version' => 1,
    ]);
    $section = DocumentTemplateSection::factory()->create([
        'template_version_id' => $version->getKey(),
        'title' => 'Locked Purpose',
        'content' => '<p>Original locked content.</p>',
    ]);

    $generator = mock(TemplateGenerator::class);
    $generator->shouldNotReceive('completeSection');
    $generator->shouldNotReceive('generateSectionTitles');

    (new CompleteTemplateSectionWithAiJob($section))->handle($generator);
    (new GenerateTemplateSectionTitlesJob($version))->handle($generator);

    expect($section->refresh()->content)->toBe('<p>Original locked content.</p>')
        ->and($section->refresh()->title)->toBe('Locked Purpose');
});

it('creates a draft revision cloned from the published version', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $template = DocumentTemplate::factory()->create([
        'created_by' => $user->getKey(),
        'document_type_id' => DocumentType::query()->firstOrFail(),
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);
    $published = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $template->getKey(),
        'version' => 1,
        'change_reason' => 'Initial release',
    ]);
    DocumentTemplateSection::factory()->create([
        'template_version_id' => $published->getKey(),
        'title' => 'Purpose',
        'content' => '<p>Published purpose.</p>',
        'section_order' => 1,
    ]);
    DocumentTemplateVariable::factory()->create([
        'template_version_id' => $published->getKey(),
        'name' => 'owner_name',
        'label' => 'Owner',
        'default_value' => 'QA',
    ]);

    $draft = app(CreateTemplateDraftRevisionAction::class)->execute(
        $template,
        $user,
        'Update responsibilities after process change.',
    );

    expect($draft->version)->toBe(2)
        ->and($draft->template_status_id)->toBe(TemplateStatus::idFor(TemplateStatus::DRAFT))
        ->and($draft->approval_status)->toBe(TemplateApprovalStatus::Draft)
        ->and($draft->change_reason)->toBe('Update responsibilities after process change.')
        ->and($draft->sections)->toHaveCount(1)
        ->and($draft->sections->first()->title)->toBe('Purpose')
        ->and($draft->sections->first()->content)->toBe('<p>Published purpose.</p>')
        ->and($draft->variables)->toHaveCount(1)
        ->and($draft->variables->first()->name)->toBe('owner_name')
        ->and($published->refresh()->isContentEditable())->toBeFalse()
        ->and($draft->isContentEditable())->toBeTrue()
        ->and($template->refresh()->templateStatus?->code)->toBe(TemplateStatus::PUBLISHED)
        ->and($template->canBeEditedBy($user))->toBeTrue()
        ->and($template->canStartDraftRevisionBy($user))->toBeFalse();

    expect(fn () => app(CreateTemplateDraftRevisionAction::class)->execute(
        $template,
        $user,
        'Another revision.',
    ))->toThrow(ValidationException::class);

    Livewire::test(EditDocumentTemplate::class, ['record' => $template->getKey()])
        ->assertSuccessful();

    Livewire::test(SectionRelationManager::class, [
        'ownerRecord' => $template->fresh(),
        'pageClass' => EditDocumentTemplate::class,
    ])
        ->assertActionVisible(TestAction::make('create')->table())
        ->assertActionVisible(TestAction::make('generateSectionTitlesWithAi')->table());
});
