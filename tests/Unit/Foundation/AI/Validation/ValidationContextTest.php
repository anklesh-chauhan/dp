<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

it('stores the artifact type', function (): void {
    $context = new ValidationContext('sop');

    expect($context->artifactType())->toBe('sop');
});

it('stores attributes', function (): void {
    $context = new ValidationContext(
        artifactType: 'sop',
        attributes: [
            'provider' => 'groq',
            'model' => 'qwen3',
        ],
    );

    expect($context->attributes())
        ->toBe([
            'provider' => 'groq',
            'model' => 'qwen3',
        ]);
});

it('determines whether an attribute exists', function (): void {
    $context = new ValidationContext(
        artifactType: 'sop',
        attributes: [
            'provider' => 'groq',
        ],
    );

    expect($context->has('provider'))->toBeTrue()
        ->and($context->has('model'))->toBeFalse();
});

it('returns an attribute value', function (): void {
    $context = new ValidationContext(
        artifactType: 'sop',
        attributes: [
            'provider' => 'groq',
        ],
    );

    expect($context->get('provider'))->toBe('groq');
});

it('returns the default value when an attribute does not exist', function (): void {
    $context = new ValidationContext('sop');

    expect($context->get('provider', 'ollama'))
        ->toBe('ollama');
});
