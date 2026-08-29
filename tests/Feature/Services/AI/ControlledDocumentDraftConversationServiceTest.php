<?php

declare(strict_types=1);

use App\Ai\Agents\ControlledDocumentDraftAgent;
use App\Filament\Pages\ControlledDocumentDraftAssistant;
use App\Jobs\ProcessControlledDocumentDraftRequest;
use App\Models\AiExecution;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentDraftRequest;
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
use App\Services\AI\Enums\ControlledDocumentDraftRequestStatus;
use App\Services\AI\Enums\ControlledDocumentDraftSessionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Models\ConversationMessage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    config()->set('modules.enabled', ['dms', 'ai']);
    config()->set('ai.providers.gemini.enabled', true);
    config()->set('ai.routing.controlled_document_drafting', ['gemini']);
    config()->set('ai.governance.classification_providers.internal', ['gemini']);

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

it('reconciles contradictory model output against required fields and preserves existing values', function (): void {
    DocumentTemplateVariable::factory()->create([
        'template_version_id' => $this->version,
        'name' => 'review_date',
        'label' => 'Review Date',
        'required' => false,
        'default_value' => null,
    ]);
    ControlledDocumentDraftAgent::fake([
        [
            'assistant_message' => 'Please provide the review date.',
            'title' => 'Change Control Policy',
            'brief' => [
                'purpose' => 'Control document changes.',
                'scope' => 'All Quality personnel.',
                'responsibilities' => 'Quality owns the process.',
                'procedure' => 'Initiate, assess, approve, and close each change.',
                'references' => '',
                'additional_details' => '',
            ],
            'variables' => [
                'purpose' => '',
                'scope' => '',
                'procedure' => '',
                'review_date' => '2029-08-29',
            ],
            'missing_details' => ['Review Date'],
            'ready_for_preview' => false,
        ],
    ])->preventStrayPrompts();
    $service = app(ControlledDocumentDraftConversationService::class);
    $session = $service->start($this->user, $this->version->id, $this->owner->id);
    $session->forceFill([
        'title' => 'Change Control Policy',
        'draft_variables' => [
            'purpose' => 'Control document changes.',
            'scope' => 'All Quality personnel.',
            'procedure' => 'Initiate, assess, approve, and close each change.',
            'review_date' => '2028-08-29',
        ],
    ])->save();

    $result = $service->respond($session->fresh(), $this->user, 'Set the review date to 29/08/2029.');
    $persistedResponse = json_decode(
        ConversationMessage::query()
            ->where('conversation_id', $session->refresh()->conversation_id)
            ->where('role', 'assistant')
            ->latest('created_at')
            ->value('content'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($result)
        ->assistant_message->toBe('The controlled-document preview is ready for review.')
        ->missing_details->toBe([])
        ->ready_for_preview->toBeTrue()
        ->and($result['variables'])
        ->purpose->toBe('Control document changes.')
        ->scope->toBe('All Quality personnel.')
        ->procedure->toBe('Initiate, assess, approve, and close each change.')
        ->review_date->toBe('2029-08-29')
        ->and($persistedResponse)
        ->assistant_message->toBe('The controlled-document preview is ready for review.')
        ->missing_details->toBe([])
        ->ready_for_preview->toBeTrue()
        ->and($session->status)->toBe(ControlledDocumentDraftSessionStatus::PREVIEW_READY);

    ConversationMessage::query()
        ->where('conversation_id', $session->conversation_id)
        ->where('role', 'assistant')
        ->latest('created_at')
        ->firstOrFail()
        ->forceFill([
            'content' => json_encode([
                ...$persistedResponse,
                'assistant_message' => 'Please provide the review date.',
            ], JSON_THROW_ON_ERROR),
        ])->save();

    Livewire::actingAs($this->user)
        ->test(ControlledDocumentDraftAssistant::class)
        ->call('openDraftSession', $session->getKey())
        ->assertSee('The controlled-document preview is ready for review.')
        ->assertDontSee('Please provide the review date.');
});

it('normalizes human-readable relationship and choice values before saving a preview', function (): void {
    $departmentType = VariableDataType::query()->create([
        'name' => 'Department',
        'code' => VariableDataType::DEPARTMENT,
    ]);
    $employeeType = VariableDataType::query()->create([
        'name' => 'Employee',
        'code' => VariableDataType::EMPLOYEE,
    ]);
    $selectType = VariableDataType::query()->create([
        'name' => 'Select',
        'code' => VariableDataType::SELECT,
    ]);
    $department = $this->version->template->department;
    $department->update(['name' => 'Quality Assurance', 'code' => 'QA']);
    $approver = User::factory()->create(['name' => 'SOP Approver']);

    foreach ([
        ['name' => 'department', 'type' => $departmentType, 'required' => true, 'options' => null],
        ['name' => 'approved_by', 'type' => $employeeType, 'required' => false, 'options' => null],
        ['name' => 'prepared_by', 'type' => $employeeType, 'required' => false, 'options' => null],
        [
            'name' => 'equipment_category',
            'type' => $selectType,
            'required' => false,
            'options' => ['lab' => 'Laboratory Equipment'],
        ],
    ] as $definition) {
        DocumentTemplateVariable::factory()->create([
            'template_version_id' => $this->version,
            'name' => $definition['name'],
            'label' => str($definition['name'])->replace('_', ' ')->title()->toString(),
            'variable_data_type_id' => $definition['type'],
            'required' => $definition['required'],
            'options' => $definition['options'],
            'default_value' => null,
        ]);
    }

    ControlledDocumentDraftAgent::fake([[
        ...draftingResponse(
            ready: true,
            purpose: 'Control document changes.',
            scope: 'All Quality personnel.',
            procedure: 'Initiate, assess, approve, and close each change.',
        ),
        'variables' => [
            'purpose' => 'Control document changes.',
            'scope' => 'All Quality personnel.',
            'procedure' => 'Initiate, assess, approve, and close each change.',
            'department' => 'QA',
            'approved_by' => 'SOP Approver',
            'prepared_by' => 'QualiGxP',
            'equipment_category' => 'Laboratory Equipment',
        ],
    ]])->preventStrayPrompts();

    $service = app(ControlledDocumentDraftConversationService::class);
    $session = $service->start($this->user, $this->version->id, $this->owner->id);
    $result = $service->respond($session, $this->user, 'Draft the SOP.');

    expect($result['ready_for_preview'])->toBeTrue()
        ->and($result['variables']['department'])->toBe($department->getKey())
        ->and($result['variables']['approved_by'])->toBe($approver->getKey())
        ->and($result['variables']['prepared_by'])->toBe('')
        ->and($result['variables']['equipment_category'])->toBe('lab');
});

it('queues one drafting request per session and shows its status in Filament', function (): void {
    Queue::fake();
    $service = app(ControlledDocumentDraftConversationService::class);
    $session = $service->start($this->user, $this->version->id, $this->owner->id);

    $request = $service->queueResponse(
        $session,
        $this->user,
        'Draft a queued change-control policy.',
    );

    expect($request->status)->toBe(ControlledDocumentDraftRequestStatus::QUEUED)
        ->and($request->message)->toBe('Draft a queued change-control policy.');

    Queue::assertPushed(
        ProcessControlledDocumentDraftRequest::class,
        fn (ProcessControlledDocumentDraftRequest $job): bool => $job->draftRequestId === $request->getKey(),
    );

    expect(fn () => $service->queueResponse(
        $session->fresh(),
        $this->user,
        'Queue another request too soon.',
    ))->toThrow(ValidationException::class, 'current drafting request');

    Livewire::actingAs($this->user)
        ->test(ControlledDocumentDraftAssistant::class)
        ->set('draftSessionId', $session->getKey())
        ->set('draftRequestId', $request->getKey())
        ->assertSee('Request queued')
        ->assertSee('Waiting for an available background worker');
});

it('processes a queued drafting request and stores the completed preview', function (): void {
    Queue::fake();
    ControlledDocumentDraftAgent::fake([
        draftingResponse(
            ready: true,
            purpose: 'Control queued document changes.',
            scope: 'All Quality personnel.',
            procedure: 'Initiate, assess, approve, and close each change.',
        ),
    ])->preventStrayPrompts();
    $service = app(ControlledDocumentDraftConversationService::class);
    $session = $service->start($this->user, $this->version->id, $this->owner->id);
    $request = $service->queueResponse($session, $this->user, 'Create the policy.');

    (new ProcessControlledDocumentDraftRequest($request->getKey()))->handle($service);

    $request->refresh();
    $session->refresh();

    expect($request->status)->toBe(ControlledDocumentDraftRequestStatus::COMPLETED)
        ->and($request->preview_hash)->toBe($session->preview_hash)
        ->and($request->started_at)->not->toBeNull()
        ->and($request->completed_at)->not->toBeNull()
        ->and($session->status)->toBe(ControlledDocumentDraftSessionStatus::PREVIEW_READY)
        ->and($session->preview_revision)->toBe(1)
        ->and(ConversationMessage::query()->where('conversation_id', $session->conversation_id)->count())->toBe(2);
});

it('finalizes a saved preview on retry without prompting the agent twice', function (): void {
    Queue::fake();
    ControlledDocumentDraftAgent::fake()->preventStrayPrompts();
    $service = app(ControlledDocumentDraftConversationService::class);
    $session = $service->start($this->user, $this->version->id, $this->owner->id);
    $request = $service->queueResponse($session, $this->user, 'Create the policy.');

    $session->forceFill([
        'preview_revision' => 1,
        'preview_hash' => str_repeat('a', 64),
    ])->save();
    $request->forceFill([
        'status' => ControlledDocumentDraftRequestStatus::PROCESSING,
        'initial_preview_revision' => 0,
        'started_at' => now(),
    ])->save();

    (new ProcessControlledDocumentDraftRequest($request->getKey()))->handle($service);

    expect($request->refresh()->status)->toBe(ControlledDocumentDraftRequestStatus::COMPLETED)
        ->and($request->preview_hash)->toBe(str_repeat('a', 64));

    ControlledDocumentDraftAgent::assertNeverPrompted();
});

it('marks an exhausted drafting job as failed without exposing provider details', function (): void {
    $request = ControlledDocumentDraftRequest::factory()->create([
        'controlled_document_draft_session_id' => app(ControlledDocumentDraftConversationService::class)
            ->start($this->user, $this->version->id, $this->owner->id),
        'requested_by' => $this->user,
        'status' => ControlledDocumentDraftRequestStatus::PROCESSING,
        'initial_preview_revision' => 0,
        'started_at' => now(),
    ]);

    (new ProcessControlledDocumentDraftRequest($request->getKey()))
        ->failed(new RuntimeException('Secret provider response.'));

    expect($request->refresh()->status)->toBe(ControlledDocumentDraftRequestStatus::FAILED)
        ->and($request->failure_message)->not->toContain('Secret provider response')
        ->and($request->failed_at)->not->toBeNull();
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

it('enforces provider governance and per-user request limits', function (): void {
    ControlledDocumentDraftAgent::fake([
        draftingResponse(false, 'Purpose', '', ''),
    ])->preventStrayPrompts();
    config()->set('ai.governance.rate_limits.controlled_document_drafting', 1);
    $service = app(ControlledDocumentDraftConversationService::class);
    $session = $service->start($this->user, $this->version->id, $this->owner->id);

    $service->respond($session, $this->user, 'First request.');

    expect(fn () => $service->respond($session->fresh(), $this->user, 'Second request.'))
        ->toThrow(ValidationException::class, 'request limit');

    Cache::flush();
    config()->set('ai.governance.classification_providers.internal', ['ollama']);

    expect(fn () => $service->respond($session->fresh(), $this->user, 'Disallowed provider request.'))
        ->toThrow(ValidationException::class, 'No AI provider is enabled');
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
