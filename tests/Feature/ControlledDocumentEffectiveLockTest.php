<?php

declare(strict_types=1);

use App\Domain\DMS\Actions\CreateDocumentRevisionAction;
use App\Filament\Resources\ControlledDocuments\Pages\EditControlledDocument;
use App\Filament\Resources\ControlledDocuments\Pages\ViewControlledDocument;
use App\Filament\Resources\ControlledDocuments\RelationManagers\DocumentSectionRelationManager;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\User;
use App\Policies\ControlledDocumentPolicy;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    config(['modules.enabled' => ['dms']]);
});

function controlledDocumentWithStatus(User $user, string $status): ControlledDocument
{
    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::query()->firstOrFail()->getKey(),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $template->getKey(),
    ]);

    return ControlledDocument::factory()->create([
        'template_id' => $template->getKey(),
        'template_version_id' => $templateVersion->getKey(),
        'document_status_id' => DocumentStatus::idFor($status),
        'owner_id' => $user->getKey(),
        'created_by' => $user->getKey(),
    ]);
}

it('locks effective controlled documents from editing and only allows creating a revision', function (): void {
    $user = User::factory()->create();
    actingAs($user);
    Gate::before(static fn (): bool => true);

    $document = controlledDocumentWithStatus($user, DocumentStatus::EFFECTIVE);
    ControlledDocumentSection::factory()->create([
        'document_id' => $document->getKey(),
        'title' => 'Purpose',
        'section_order' => 1,
    ]);

    expect($document->isEditable())->toBeFalse()
        ->and($document->canBeEditedBy($user))->toBeFalse();

    Livewire::test(EditControlledDocument::class, ['record' => $document->getKey()])
        ->assertRedirect(ViewControlledDocument::getUrl(['record' => $document]));

    Livewire::test(ViewControlledDocument::class, ['record' => $document->getKey()])
        ->assertActionHidden('edit')
        ->assertActionVisible('createRevision');

    Livewire::test(DocumentSectionRelationManager::class, [
        'ownerRecord' => $document,
        'pageClass' => ViewControlledDocument::class,
    ])
        ->assertActionHidden(TestAction::make('create')->table())
        ->assertActionHidden(TestAction::make('edit')->table($document->sections()->first()))
        ->assertActionVisible(TestAction::make('view')->table($document->sections()->first()));

    $revision = app(CreateDocumentRevisionAction::class)->execute(
        $document,
        $user,
        'Correct a controlled procedure step.',
    );

    expect($revision->documentStatus?->code)->toBe(DocumentStatus::DRAFT)
        ->and($revision->canBeEditedBy($user))->toBeTrue()
        ->and($document->refresh()->isEditable())->toBeFalse();
});

it('still allows editing unlocked draft controlled documents', function (): void {
    $user = User::factory()->create();
    actingAs($user);
    Gate::before(static fn (): bool => true);

    $document = controlledDocumentWithStatus($user, DocumentStatus::DRAFT);

    expect($document->isEditable())->toBeTrue()
        ->and($document->canBeEditedBy($user))->toBeTrue();

    Livewire::test(EditControlledDocument::class, ['record' => $document->getKey()])
        ->assertSuccessful();

    Livewire::test(ViewControlledDocument::class, ['record' => $document->getKey()])
        ->assertActionVisible('edit')
        ->assertActionHidden('createRevision');
});

it('denies update authorization for effective documents through the policy', function (): void {
    Permission::findOrCreate('Update:ControlledDocument', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('Update:ControlledDocument');

    $document = controlledDocumentWithStatus($user, DocumentStatus::EFFECTIVE);
    $draft = controlledDocumentWithStatus($user, DocumentStatus::DRAFT);
    $policy = app(ControlledDocumentPolicy::class);

    expect($policy->update($user, $document))->toBeFalse()
        ->and($policy->update($user, $draft))->toBeTrue();
});
