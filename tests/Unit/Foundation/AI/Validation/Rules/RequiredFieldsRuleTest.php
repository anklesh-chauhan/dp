<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Rules\RequiredFieldsRule;
use App\Foundation\AI\Validation\Support\ArtifactAccessor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

it('passes when all required fields exist', function (): void {
    $artifact = [
        'title' => 'SOP',
        'metadata' => [
            'version' => '1.0',
        ],
    ];

    $rule = new RequiredFieldsRule(
        new ArtifactAccessor(),
        [
            'title',
            'metadata.version',
        ],
    );

    $issues = iterator_to_array(
        $rule->validate(
            $artifact,
            new ValidationContext('generic_artifact'),
        ),
    );

    expect($issues)->toBeEmpty();
});

it('returns an issue for each missing required field', function (): void {
    $artifact = [
        'title' => 'SOP',
    ];

    $rule = new RequiredFieldsRule(
        new ArtifactAccessor(),
        [
            'title',
            'metadata.version',
            'document_number',
        ],
    );

    $issues = iterator_to_array(
        $rule->validate(
            $artifact,
            new ValidationContext('generic_artifact'),
        ),
    );

    expect($issues)
        ->toHaveCount(2)
        ->and($issues[0]->code())->toBe('required_field_missing')
        ->and($issues[0]->path())->toBe('metadata.version')
        ->and($issues[1]->path())->toBe('document_number');
});

it('treats null values as existing fields', function (): void {
    $artifact = [
        'title' => null,
    ];

    $rule = new RequiredFieldsRule(
        new ArtifactAccessor(),
        ['title'],
    );

    $issues = iterator_to_array(
        $rule->validate(
            $artifact,
            new ValidationContext('generic_artifact'),
        ),
    );

    expect($issues)->toBeEmpty();
});
