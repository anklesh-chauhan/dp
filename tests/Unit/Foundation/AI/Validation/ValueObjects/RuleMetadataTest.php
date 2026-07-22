<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Enums\RuleCategory;
use App\Foundation\AI\Validation\Enums\RuleExecutionMode;
use App\Foundation\AI\Validation\Enums\ValidationSeverity;
use App\Foundation\AI\Validation\Exceptions\InvalidRuleMetadataException;
use App\Foundation\AI\Validation\ValueObjects\RuleMetadata;

it('creates immutable rule metadata', function (): void {
    $metadata = new RuleMetadata(
        code: 'required-fields',
        name: 'Required Fields',
        category: RuleCategory::STRUCTURE,
        defaultSeverity: ValidationSeverity::ERROR,
        executionMode: RuleExecutionMode::SEQUENTIAL,
        version: '1.0.0',
    );

    expect($metadata->code)->toBe('required-fields')
        ->and($metadata->name)->toBe('Required Fields')
        ->and($metadata->category)->toBe(RuleCategory::STRUCTURE)
        ->and($metadata->defaultSeverity)->toBe(ValidationSeverity::ERROR)
        ->and($metadata->executionMode)->toBe(RuleExecutionMode::SEQUENTIAL)
        ->and($metadata->version)->toBe('1.0.0');
});

it('rejects an empty rule code', function (): void {
    new RuleMetadata(
        code: '',
        name: 'Required Fields',
        category: RuleCategory::STRUCTURE,
        defaultSeverity: ValidationSeverity::ERROR,
        executionMode: RuleExecutionMode::SEQUENTIAL,
        version: '1.0.0',
    );
})->throws(
    InvalidRuleMetadataException::class,
    'Rule code cannot be empty.',
);

it('rejects an empty rule name', function (): void {
    new RuleMetadata(
        code: 'required-fields',
        name: '',
        category: RuleCategory::STRUCTURE,
        defaultSeverity: ValidationSeverity::ERROR,
        executionMode: RuleExecutionMode::SEQUENTIAL,
        version: '1.0.0',
    );
})->throws(
    InvalidRuleMetadataException::class,
    'Rule name cannot be empty.',
);

it('rejects an empty rule version', function (): void {
    new RuleMetadata(
        code: 'required-fields',
        name: 'Required Fields',
        category: RuleCategory::STRUCTURE,
        defaultSeverity: ValidationSeverity::ERROR,
        executionMode: RuleExecutionMode::SEQUENTIAL,
        version: '',
    );
})->throws(
    InvalidRuleMetadataException::class,
    'Rule version cannot be empty.',
);
