<?php

declare(strict_types=1);

use App\Services\AI\Validation\GeneratedTemplateValidator;

beforeEach(function (): void {
    $this->validator = new GeneratedTemplateValidator();
});

it('accepts a valid generated template', function (): void {
    $template = [
        'sections' => [
            [
                'title' => 'Document Control',
                'content' => 'This document becomes effective on {{effective_date}} and is approved by {{approved_by}}.',
                'section_order' => 1,
                'section_type' => 'rich_text',
            ],
        ],
        'variables' => [
            [
                'name' => 'effective_date',
                'label' => 'Effective Date',
                'datatype' => 'date',
                'default_value' => '',
                'required' => true,
            ],
            [
                'name' => 'approved_by',
                'label' => 'Approved By',
                'datatype' => 'text',
                'default_value' => '',
                'required' => true,
            ],
        ],
    ];

    expect(fn () => $this->validator->validate($template))
        ->not
        ->toThrow(\InvalidArgumentException::class);
});

it('rejects an unreferenced variable', function (): void {
    $template = [
        'sections' => [
            [
                'content' => 'This document becomes effective on {{effective_date}}.',
            ],
        ],
        'variables' => [
            [
                'name' => 'effective_date',
            ],
            [
                'name' => 'approved_by',
            ],
        ],
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Generated template contains unreferenced variables: approved_by.',
        );
});

it('rejects an undefined placeholder', function (): void {
    $template = [
        'sections' => [
            [
                'content' => 'Effective on {{effective_date}} and approved by {{approved_by}}.',
            ],
        ],
        'variables' => [
            [
                'name' => 'effective_date',
            ],
        ],
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Generated template contains undefined placeholders: approved_by.',
        );
});

it('rejects duplicate variable names', function (): void {
    $template = [
        'sections' => [
            [
                'content' => 'Effective on {{effective_date}}.',
            ],
        ],
        'variables' => [
            [
                'name' => 'effective_date',
            ],
            [
                'name' => 'effective_date',
            ],
        ],
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Generated template contains duplicate variables: effective_date.',
        );
});

it('rejects variable names that are not snake case', function (): void {
    $template = [
        'sections' => [
            [
                'content' => 'Effective on {{effectiveDate}}.',
            ],
        ],
        'variables' => [
            [
                'name' => 'effectiveDate',
            ],
        ],
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Generated variable [effectiveDate] must use snake_case.',
        );
});

it('rejects malformed sections collection', function (): void {
    $template = [
        'sections' => 'invalid',
        'variables' => [],
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Generated template sections must be an array.',
        );
});

it('rejects malformed section items', function (): void {
    $template = [
        'sections' => [
            [
                'content' => 'Valid section.',
            ],
            'invalid section',
        ],
        'variables' => [],
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Every generated template section must be an array.',
        );
});

it('rejects malformed variables collection', function (): void {
    $template = [
        'sections' => [],
        'variables' => 'invalid',
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Generated template variables must be an array.',
        );
});

it('rejects malformed variable items', function (): void {
    $template = [
        'sections' => [],
        'variables' => [
            [
                'name' => 'effective_date',
            ],
            'invalid variable',
        ],
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Every generated template variable must be an array.',
        );
});

it('accepts a variable referenced multiple times', function (): void {
    $template = [
        'sections' => [
            [
                'content' => 'Effective on {{effective_date}}.',
            ],
            [
                'content' => 'Review after {{effective_date}}.',
            ],
        ],
        'variables' => [
            [
                'name' => 'effective_date',
            ],
        ],
    ];

    expect(fn () => $this->validator->validate($template))
        ->not
        ->toThrow(\InvalidArgumentException::class);
});

it('accepts empty sections and empty variables', function (): void {
    $template = [
        'sections' => [],
        'variables' => [],
    ];

    expect(fn () => $this->validator->validate($template))
        ->not
        ->toThrow(\InvalidArgumentException::class);
});

it('rejects a variable with a missing name', function (): void {
    $template = [
        'sections' => [],
        'variables' => [
            [
                'label' => 'Effective Date',
            ],
        ],
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Generated variable at index [0] is missing its name.',
        );
});

it('rejects a variable with a non string name', function (): void {
    $template = [
        'sections' => [],
        'variables' => [
            [
                'name' => 123,
            ],
        ],
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Generated variable name at index [0] must be a string.',
        );
});

it('rejects a variable with an empty name', function (): void {
    $template = [
        'sections' => [],
        'variables' => [
            [
                'name' => '   ',
            ],
        ],
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Generated variable name at index [0] must not be empty.',
        );
});

it('rejects a section with missing content', function (): void {
    $template = [
        'sections' => [
            [
                'title' => 'Purpose',
            ],
        ],
        'variables' => [],
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Generated section at index [0] is missing its content.',
        );
});

it('rejects a section with non string content', function (): void {
    $template = [
        'sections' => [
            [
                'content' => ['invalid'],
            ],
        ],
        'variables' => [],
    ];

    expect(fn () => $this->validator->validate($template))
        ->toThrow(
            \InvalidArgumentException::class,
            'Generated section content at index [0] must be a string.',
        );
});

it('accepts an empty section content string', function (): void {
    $template = [
        'sections' => [
            [
                'content' => '',
            ],
        ],
        'variables' => [],
    ];

    expect(fn () => $this->validator->validate($template))
        ->not
        ->toThrow(\InvalidArgumentException::class);
});
