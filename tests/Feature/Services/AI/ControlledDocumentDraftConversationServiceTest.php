<?php

declare(strict_types=1);

use App\Ai\Agents\ControlledDocumentDraftAgent;
use App\Models\AiExecution;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateSection;
use App\Models\DocumentTemplateVariable;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Models\VariableDataType;
use App\Services\AI\ControlledDocumentDraftConversationService;
use App\Services\AI\Enums\AiExecutionStatus;
use App\Services\AI\Enums\ControlledDocumentDraftSessionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Models\ConversationMessage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    config()->set('modules.enabled', ['dms', 'ai']);
    config()->set('ai.providers.gemini.enabled', true);
    config()->set('ai.routing.controlled_document_drafting', ['gemini']);

    foreach ([
        TemplateStatus::DRAFT => 'Draft',
        TemplateStatus::PUBLISHED => 'Published',
    ] as $code => $name) {
        TemplateStatus::query()->create(compact('code', 'name'));
    }

    VariableDataType::query()->create([
        'name' => 'Text',
        'code' => VariableDataType::TEXT,
    ]);

    $this->user = User::factory()->create();
    $this->owner = User::factory()->create();
    Gate::before(static fn (): bool => true);

    $department = Department::factory()->create();
    $documentType = DocumentType::factory()->create(['code' => DocumentType::POLICY]);
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => DocumentCategory::factory(),
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
        'current_version' => 1,
    ]);
    $this->version = DocumentTemplateVersion::factory()
        ->published()
        ->create([
            'document_template_id' => $template,
            'version' => 1,
        ]);

    foreach (['purpose', 'scope', 'procedure'] as $name) {
        DocumentTemplateVariable::factory()->create([
            'template_version_id' => $this->version,
            'name' => $name,
            'label' => str($name)->title()->toString(),
            'required' => true,
            'default_value' => null,
        ]);
    }

    DocumentTemplateSection::factory()->create([
        'template_version_id' => $this->version,
        'title' => 'Purpose',
        'section_order' => 1,
        'content' => '{{purpose}}',
    ]);
});

it('continues a structured conversation without creating a controlled document', function (): void {
    ControlledDocumentDraftAgent::fake(
        fn (string $prompt): array => str_contains($prompt, 'scope is')
            ? draftingResponse(
                ready: true,
                purpose: 'Control document changes.',
                scope: 'All Quality personnel.',
                procedure: 'Initiate, assess, approve, and close each change.',
            )
            : draftingResponse(ready: false, purpose: 'Control document changes.', scope: '', procedure: ''),
    )->preventStrayPrompts();

    $service = app(ControlledDocumentDraftConversationService::class);
    $session = $service->start($this->user, $this->version->id, $this->owner->id);

    $first = $service->respond($session, $this->user, 'Draft a change-control policy.');
    $conversationId = $session->refresh()->conversation_id;
    $second = $service->respond($session->refresh(), $this->user, 'The scope is all Quality personnel.');

    expect($first['ready_for_preview'])->toBeFalse()
        ->and($second['ready_for_preview'])->toBeTrue()
        ->and($session->refresh()->status)->toBe(ControlledDocumentDraftSessionStatus::PREVIEW_READY)
        ->and($session->conversation_id)->toBe($conversationId)
        ->and($session->preview_revision)->toBe(2)
        ->and($session->preview_hash)->not->toBeEmpty()
        ->and(ConversationMessage::query()->where('conversation_id', $conversationId)->count())->toBe(4)
        ->and(ControlledDocument::query()->count())->toBe(0)
        ->and($session->refresh()->draft_variables['procedure'])->toContain('Initiate');

    $execution = AiExecution::query()->latest('id')->firstOrFail();

    expect($execution->status)->toBe(AiExecutionStatus::SUCCEEDED)
        ->and($execution->successful_provider)->toBe('gemini');
});

it('rejects access to another users draft session', function (): void {
    $session = app(ControlledDocumentDraftConversationService::class)
        ->start($this->user, $this->version->id, $this->owner->id);

    expect(fn () => app(ControlledDocumentDraftConversationService::class)->respond(
        $session,
        User::factory()->create(),
        'Change the procedure.',
    ))->toThrow(ValidationException::class);
});

/**
 * @return array<string, mixed>
 */
function draftingResponse(
    bool $ready,
    string $purpose,
    string $scope,
    string $procedure,
): array {
    return [
        'assistant_message' => $ready
            ? 'The controlled-document preview is ready.'
            : 'Please provide the scope and procedure.',
        'title' => 'Change Control Policy',
        'brief' => [
            'purpose' => $purpose,
            'scope' => $scope,
            'responsibilities' => 'Quality owns the process.',
            'procedure' => $procedure,
            'references' => '',
            'additional_details' => '',
        ],
        'variables' => compact('purpose', 'scope', 'procedure'),
        'missing_details' => $ready ? [] : ['Scope', 'Procedure'],
        'ready_for_preview' => $ready,
    ];
}
