<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

describe('DocumentTemplateIntegrityService', function (): void {

    it('returns a passing validation result for a valid template', function (): void {
        $template = [
            'variables' => [
                [
                    'name' => 'batch_number',
                    'label' => 'Batch Number',
                    'datatype' => 'text',
                    'default_value' => '',
                    'required' => true,
                ],
                [
                    'name' => 'expiry_date',
                    'label' => 'Expiry Date',
                    'datatype' => 'date',
                    'default_value' => '',
                    'required' => true,
                ],
            ],
            'sections' => [
                [
                    'title' => 'Product details',
                    'content' => '{{batch_number}} {{expiry_date}}',
                    'section_order' => 1,
                    'section_type' => 'rich_text',
                ],
            ],
        ];

        $result = createIntegrityService()->validate($template);

        expect($result)
            ->toBeInstanceOf(ValidationResult::class);

        expect($result->passed())->toBeTrue();
        expect($result->failed())->toBeFalse();
        expect($result->issues()->isEmpty())->toBeTrue();
        expect($result->hasErrors())->toBeFalse();
        expect($result->hasWarnings())->toBeFalse();
    });

    it('returns a failing validation result for an invalid template', function (): void {
        $template = [
            'variables' => [
                [
                    'name' => 'BatchNumber',
                    'label' => 'Batch Number',
                    'datatype' => 'text',
                    'default_value' => '',
                    'required' => true,
                ],
            ],
            'sections' => [
                [
                    'title' => 'Product details',
                    'content' => '{{expiry_date}}',
                    'section_order' => 1,
                    'section_type' => 'rich_text',
                ],
            ],
        ];

        $result = createIntegrityService()->validate($template);

        expect($result)
            ->toBeInstanceOf(ValidationResult::class);

        expect($result->passed())->toBeFalse();
        expect($result->failed())->toBeTrue();
        expect($result->hasErrors())->toBeTrue();
        expect($result->issues()->isNotEmpty())->toBeTrue();

        $codes = array_map(
            static fn ($issue) => $issue->code(),
            $result->issues()->all(),
        );

        expect($codes)
            ->toContain('snake_case_variable_names')
            ->toContain('defined_placeholders')
            ->toContain('referenced_variables');
    });
});
