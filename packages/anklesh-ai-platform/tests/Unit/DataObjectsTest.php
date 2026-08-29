<?php

declare(strict_types=1);

use Anklesh\AiPlatform\Data\AiRequest;
use Anklesh\AiPlatform\Data\AiResponse;
use Anklesh\AiPlatform\Enums\DataClassification;

it('normalizes standard and custom classifications', function () {
    expect((new AiRequest('Hello'))->classificationValue())->toBe('internal')
        ->and((new AiRequest(
            prompt: 'Hello',
            classification: DataClassification::Confidential,
        ))->classificationValue())->toBe('confidential')
        ->and((new AiRequest(
            prompt: 'Hello',
            classification: '  Customer-Private  ',
        ))->classificationValue())->toBe('customer-private');
});

it('validates request invariants', function (array $arguments, string $message) {
    expect(fn () => new AiRequest(...$arguments))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'empty prompt' => [
        ['prompt' => '   '],
        'The AI prompt may not be empty.',
    ],
    'empty use case' => [
        ['prompt' => 'Hello', 'useCase' => '   '],
        'The AI use case may not be empty.',
    ],
    'invalid timeout' => [
        ['prompt' => 'Hello', 'timeout' => 0],
        'The AI timeout must be at least one second.',
    ],
]);

it('provides type-safe text and structured response access', function () {
    $text = new AiResponse('Hello', 'openai');
    $structured = new AiResponse(['score' => 95], 'gemini');

    expect($text->text())->toBe('Hello')
        ->and($structured->structured())->toBe(['score' => 95])
        ->and(fn () => $text->structured())->toThrow(RuntimeException::class)
        ->and(fn () => $structured->text())->toThrow(RuntimeException::class);
});
