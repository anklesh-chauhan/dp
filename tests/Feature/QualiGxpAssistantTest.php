<?php

declare(strict_types=1);

use App\Ai\Agents\QualiGxpAssistantAgent;
use App\Ai\Tools\SearchControlledDocuments;
use App\Ai\Tools\SearchQualityRecords;
use App\Domain\QMS\Models\Deviation;
use App\Filament\Pages\QualiGxpAssistant;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Services\AI\QualiGxpAssistantService;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    config()->set('modules.enabled', ['dms', 'qms', 'ai']);
    config()->set('ai.providers.ollama.enabled', true);
    config()->set('ai.routing.application_assistant', ['ollama']);
    config()->set('ai.governance.classification_providers.internal', ['ollama']);
    config()->set('ai.conversations.generate_title', false);

    foreach ([
        'Use:AiAssistant',
        'ViewAny:ControlledDocument',
        'View:ControlledDocument',
        'ViewAny:Deviation',
        'View:Deviation',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    foreach ([
        DocumentStatus::DRAFT => 'Draft',
        DocumentStatus::APPROVED => 'Approved',
        DocumentStatus::EFFECTIVE => 'Effective',
    ] as $code => $name) {
        DocumentStatus::query()->create(compact('code', 'name'));
    }

    foreach ([
        TemplateStatus::DRAFT => 'Draft',
        TemplateStatus::PUBLISHED => 'Published',
    ] as $code => $name) {
        TemplateStatus::query()->create(compact('code', 'name'));
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo([
        'Use:AiAssistant',
        'ViewAny:ControlledDocument',
        'View:ControlledDocument',
        'ViewAny:Deviation',
        'View:Deviation',
    ]);
});

it('searches only approved or effective controlled documents and returns citations', function (): void {
    $template = DocumentTemplate::factory()->create([
        'document_type_id' => DocumentType::factory(),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
    ]);
    $effective = ControlledDocument::factory()->create([
        'document_number' => 'SOP-QA-1001',
        'title' => 'Sterility Assurance',
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
    ]);
    ControlledDocumentSection::factory()->create([
        'document_id' => $effective,
        'title' => 'Procedure',
        'content' => '<p>Perform the sterility assurance review before release.</p>',
    ]);
    $draft = $effective->replicate();
    $draft->forceFill([
        'document_series_id' => (string) Str::uuid(),
        'document_number' => 'SOP-QA-DRAFT',
        'title' => 'Sterility Draft',
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
    ])->save();

    $result = app()->makeWith(SearchControlledDocuments::class, ['user' => $this->user])
        ->handle(new Request(['query' => 'sterility']));

    expect((string) $result)
        ->toContain('SOP-QA-1001')
        ->toContain('citation_url')
        ->not->toContain('Sterility Draft');
});

it('enforces QMS entitlement and record permissions in quality searches', function (): void {
    $deviation = Deviation::factory()->create([
        'title' => 'Temperature excursion investigation',
    ]);
    $tool = new SearchQualityRecords($this->user, app(ModuleManager::class));

    expect((string) $tool->handle(new Request([
        'query' => 'temperature',
        'record_type' => 'deviation',
    ])))
        ->toContain($deviation->deviation_number)
        ->toContain('citation_url');

    config()->set('modules.enabled', ['dms', 'ai']);

    expect((string) $tool->handle(new Request([
        'query' => 'temperature',
        'record_type' => 'deviation',
    ])))->toBe('The QMS module is not enabled.');

    config()->set('modules.enabled', ['dms', 'qms', 'ai']);
    $unauthorized = User::factory()->create();

    expect((string) (new SearchQualityRecords($unauthorized, app(ModuleManager::class)))->handle(new Request([
        'query' => 'temperature',
        'record_type' => 'deviation',
    ])))->toBe('No authorized QMS records found.');
});

it('persists an owned read-only conversation and rejects cross-user continuation', function (): void {
    QualiGxpAssistantAgent::fake([
        'No authorized source was needed for this greeting.',
    ])->preventStrayPrompts();
    $assistant = app(QualiGxpAssistantService::class);
    $deltas = [];

    $result = $assistant->stream(
        $this->user,
        'Hello assistant.',
        function (string $delta) use (&$deltas): void {
            $deltas[] = $delta;
        },
    );

    expect($result['message'])->toContain('greeting')
        ->and($result['conversation_id'])->not->toBeEmpty()
        ->and(implode('', $deltas))->toBe($result['message'])
        ->and($deltas)->toHaveCount(8);

    $otherUser = User::factory()->create();
    $otherUser->givePermissionTo('Use:AiAssistant');

    expect(fn () => $assistant->stream(
        $otherUser,
        'Continue this conversation.',
        static function (): void {},
        $result['conversation_id'],
    ))->toThrow(AuthorizationException::class);
});

it('streams the assistant response through the Filament page', function (): void {
    QualiGxpAssistantAgent::fake([
        'This response arrives as streamed text.',
    ])->preventStrayPrompts();

    Livewire::actingAs($this->user)
        ->test(QualiGxpAssistant::class)
        ->set('userMessage', 'Stream this response.')
        ->call('sendMessage')
        ->assertSet('userMessage', '')
        ->assertSet('pendingMessage', 'Stream this response.')
        ->call('streamResponse')
        ->assertSet('pendingMessage', '')
        ->assertSet('conversationId', fn (?string $conversationId): bool => filled($conversationId))
        ->assertSee('This response arrives as streamed text.');
});

it('lists and reopens only the signed-in users QualiGxP assistant chats', function (): void {
    QualiGxpAssistantAgent::fake([
        'The first saved response.',
        'A private response for another user.',
    ])->preventStrayPrompts();
    $assistant = app(QualiGxpAssistantService::class);
    $owned = $assistant->stream(
        $this->user,
        'Find my effective validation SOP.',
        static function (): void {},
    );

    $otherUser = User::factory()->create();
    $otherUser->givePermissionTo('Use:AiAssistant');
    $assistant->stream(
        $otherUser,
        'This conversation must remain private.',
        static function (): void {},
    );

    $unrelatedConversation = Conversation::query()->create([
        'id' => (string) Str::uuid(),
        'participant_type' => $this->user->getMorphClass(),
        'participant_id' => $this->user->getKey(),
        'title' => 'Unrelated agent conversation',
    ]);
    ConversationMessage::query()->create([
        'id' => (string) Str::uuid(),
        'conversation_id' => $unrelatedConversation->getKey(),
        'participant_type' => $this->user->getMorphClass(),
        'participant_id' => $this->user->getKey(),
        'agent' => 'App\\Ai\\Agents\\AnotherAgent',
        'role' => 'user',
        'content' => 'Do not show this in QualiGxP history.',
        'attachments' => [],
        'tool_calls' => [],
        'tool_results' => [],
        'usage' => [],
        'meta' => [],
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(QualiGxpAssistant::class);

    expect($component->get('conversationHistory'))
        ->toHaveCount(1)
        ->and($component->get('conversationHistory.0.title'))
        ->toContain('Find my effective validation SOP');

    $component
        ->call('openConversation', $owned['conversation_id'])
        ->assertSet('conversationId', $owned['conversation_id'])
        ->assertSee('The first saved response.')
        ->assertDontSee('This conversation must remain private.')
        ->assertDontSee('Do not show this in QualiGxP history.');
});

it('rejects opening another users assistant chat from history', function (): void {
    QualiGxpAssistantAgent::fake(['Private response.'])->preventStrayPrompts();
    $otherUser = User::factory()->create();
    $otherUser->givePermissionTo('Use:AiAssistant');
    $result = app(QualiGxpAssistantService::class)->stream(
        $otherUser,
        'Private chat.',
        static function (): void {},
    );

    Livewire::actingAs($this->user)
        ->test(QualiGxpAssistant::class)
        ->call('openConversation', $result['conversation_id'])
        ->assertForbidden();
});

it('shows the assistant only to entitled users with explicit permission', function (): void {
    $this->actingAs($this->user);

    expect(QualiGxpAssistant::canAccess())->toBeTrue();

    config()->set('modules.enabled', ['dms', 'qms']);

    expect(QualiGxpAssistant::canAccess())->toBeFalse();
});
