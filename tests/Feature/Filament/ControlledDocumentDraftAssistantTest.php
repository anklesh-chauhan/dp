<?php

declare(strict_types=1);

use App\Filament\Pages\ControlledDocumentDraftAssistant;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentDraftSession;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
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
