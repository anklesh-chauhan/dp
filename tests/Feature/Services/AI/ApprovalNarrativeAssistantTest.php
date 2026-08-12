<?php

declare(strict_types=1);

use App\Exceptions\ModuleNotEnabledException;
use App\Services\AI\ApprovalNarrativeAssistant;
use App\Services\AI\Contracts\LLMManagerContract;
use App\Services\AI\Data\LLMRequest;
use App\Services\AI\Data\LLMResponse;
use App\Services\AI\Enums\AIDataClassification;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\ApprovalNarrativeKind;
use App\Services\AI\Enums\ApprovalNarrativeOperation;
use App\Services\AI\Enums\LLMCapability;
use App\Support\Modules\ModuleManager;
use Mockery\MockInterface;

it('creates a submission note through the llm manager', function (): void {
    config()->set('modules.enabled', ['dms', 'ai']);

    $llmManager = Mockery::mock(
        LLMManagerContract::class,
        function (MockInterface $mock): void {
            $mock
                ->shouldReceive('generate')
                ->once()
                ->withArgs(function (LLMRequest $request): bool {
                    expect($request->useCase)
                        ->toBe(AIUseCase::APPROVAL_SUBMISSION_NOTE)
                        ->and($request->capability)
                        ->toBe(LLMCapability::STRUCTURED_OUTPUT)
                        ->and($request->dataClassification)
                        ->toBe(AIDataClassification::INTERNAL)
                        ->and($request->metadata)
                        ->toMatchArray([
                            'feature' => 'approval_narrative_assistance',
                            'kind' => 'submission_note',
                            'operation' => 'create',
                        ])
                        ->and($request->prompt)
                        ->toContain('SOP-001 Cleaning Procedure')
                        ->toContain('Tell reviewers what changed');

                    return true;
                })
                ->andReturn(new LLMResponse(
                    content: ['text' => '  Updated cleaning frequency; focus on Section 4.  '],
                    provider: 'gemini',
                    model: 'gemini-test',
                ));
        },
    );

    $assistant = new ApprovalNarrativeAssistant(
        llmManager: $llmManager,
        moduleManager: app(ModuleManager::class),
    );

    $result = $assistant->transform(
        kind: ApprovalNarrativeKind::SubmissionNote,
        operation: ApprovalNarrativeOperation::Create,
        content: '',
        context: [
            'subject' => 'SOP-001 Cleaning Procedure',
            'decision' => 'Submit for review',
        ],
    );

    expect($result)->toBe('Updated cleaning frequency; focus on Section 4.');
});

it('requires existing text before polish or shorten', function (): void {
    config()->set('modules.enabled', ['dms', 'ai']);

    $llmManager = Mockery::mock(LLMManagerContract::class);
    $llmManager->shouldNotReceive('generate');

    $assistant = new ApprovalNarrativeAssistant(
        llmManager: $llmManager,
        moduleManager: app(ModuleManager::class),
    );

    expect($assistant->transform(
        kind: ApprovalNarrativeKind::DecisionRationale,
        operation: ApprovalNarrativeOperation::Polish,
        content: '   ',
    ))->toBeNull();
});

it('blocks assistance when the ai module is disabled', function (): void {
    config()->set('modules.enabled', ['dms']);

    $assistant = new ApprovalNarrativeAssistant(
        llmManager: Mockery::mock(LLMManagerContract::class),
        moduleManager: app(ModuleManager::class),
    );

    expect(fn () => $assistant->transform(
        kind: ApprovalNarrativeKind::DecisionRationale,
        operation: ApprovalNarrativeOperation::Create,
        content: '',
    ))->toThrow(ModuleNotEnabledException::class);
});

it('polishes decision rationale text', function (): void {
    config()->set('modules.enabled', ['dms', 'ai']);

    $llmManager = Mockery::mock(
        LLMManagerContract::class,
        function (MockInterface $mock): void {
            $mock
                ->shouldReceive('generate')
                ->once()
                ->withArgs(function (LLMRequest $request): bool {
                    expect($request->useCase)
                        ->toBe(AIUseCase::APPROVAL_DECISION_RATIONALE)
                        ->and($request->temperature)
                        ->toBe(0.1)
                        ->and($request->prompt)
                        ->toContain('Looks good to me');

                    return true;
                })
                ->andReturn(new LLMResponse(
                    content: ['text' => 'Reviewed sections 1-5 against the approved template and found no gaps.'],
                    provider: 'ollama',
                    model: 'test',
                ));
        },
    );

    $assistant = new ApprovalNarrativeAssistant(
        llmManager: $llmManager,
        moduleManager: app(ModuleManager::class),
    );

    expect($assistant->transform(
        kind: ApprovalNarrativeKind::DecisionRationale,
        operation: ApprovalNarrativeOperation::Polish,
        content: 'Looks good to me',
        context: ['decision' => 'Approve'],
    ))->toBe('Reviewed sections 1-5 against the approved template and found no gaps.');
});
