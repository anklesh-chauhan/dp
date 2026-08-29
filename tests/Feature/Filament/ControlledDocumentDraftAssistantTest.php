<?php

declare(strict_types=1);

use App\Filament\Pages\ControlledDocumentDraftAssistant;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentDraftRequest;
use App\Models\ControlledDocumentDraftSession;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Services\AI\Enums\ControlledDocumentDraftRequestStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    config()->set('modules.enabled', ['dms', 'ai']);

    foreach ([
        TemplateStatus::DRAFT => 'Draft',
        TemplateStatus::PUBLISHED => 'Published',
    ] as $code => $name) {
        TemplateStatus::query()->create(compact('code', 'name'));
    }

    $this->user = User::factory()->create();
    $this->owner = User::factory()->create();
    Permission::findOrCreate('Create:ControlledDocument', 'web');
    $this->user->givePermissionTo('Create:ControlledDocument');
    $this->actingAs($this->user);

    $this->template = DocumentTemplate::factory()->create([
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);
    DocumentTemplateVersion::factory()->published()->create([
        'document_template_id' => $this->template,
        'version' => 1,
    ]);
});

it('starts a private conversation without prematurely creating a document', function (): void {
    Livewire::test(ControlledDocumentDraftAssistant::class)
        ->assertSuccessful()
        ->assertSee('Start a controlled-document draft')
        ->set('templateId', $this->template->id)
        ->set('ownerId', $this->owner->id)
        ->call('startConversation')
        ->assertHasNoErrors()
        ->assertSet('draftSessionId', fn (?int $id): bool => $id !== null)
        ->assertSee('Document conversation')
        ->assertSee('This preview is not yet a controlled document.');

    expect(ControlledDocumentDraftSession::query()
        ->where('created_by', $this->user->id)
        ->count())->toBe(1)
        ->and(ControlledDocument::query()->count())->toBe(0);
});

it('blocks the page when the AI module is disabled', function (): void {
    config()->set('modules.enabled', ['dms']);

    expect(ControlledDocumentDraftAssistant::canAccess())->toBeFalse();
});

it('blocks users without controlled-document creation permission', function (): void {
    $this->actingAs(User::factory()->create());

    expect(ControlledDocumentDraftAssistant::canAccess())->toBeFalse();
});

it('lists and reopens an owned drafting chat with its latest job state', function (): void {
    $templateVersion = $this->template->publishedVersion;
    $session = ControlledDocumentDraftSession::factory()->create([
        'created_by' => $this->user,
        'template_id' => $this->template,
        'template_version_id' => $templateVersion,
        'owner_id' => $this->owner,
        'title' => 'Validation Master Plan',
    ]);
    $request = ControlledDocumentDraftRequest::factory()->create([
        'controlled_document_draft_session_id' => $session,
        'requested_by' => $this->user,
        'status' => ControlledDocumentDraftRequestStatus::QUEUED,
        'message' => 'Prepare the validation plan.',
    ]);

    $component = Livewire::test(ControlledDocumentDraftAssistant::class);

    expect($component->get('draftHistory'))
        ->toHaveCount(1)
        ->and($component->get('draftHistory.0.title'))->toBe('Validation Master Plan')
        ->and($component->get('draftHistory.0.status'))->toBe('Queued');

    $component
        ->call('openDraftSession', $session->getKey())
        ->assertSet('draftSessionId', $session->getKey())
        ->assertSet('templateId', $this->template->getKey())
        ->assertSet('ownerId', $this->owner->getKey())
        ->assertSet('draftRequestId', $request->getKey())
        ->assertSee('Request queued');
});

it('does not expose or reopen another users drafting history', function (): void {
    $session = ControlledDocumentDraftSession::factory()->create([
        'created_by' => User::factory(),
        'template_id' => $this->template,
        'template_version_id' => $this->template->publishedVersion,
        'owner_id' => $this->owner,
        'title' => 'Private draft chat',
    ]);

    $component = Livewire::test(ControlledDocumentDraftAssistant::class);

    expect($component->get('draftHistory'))->toBeEmpty();

    expect(fn () => $component->call('openDraftSession', $session->getKey()))
        ->toThrow(ModelNotFoundException::class);
});
