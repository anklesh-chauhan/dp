<?php

declare(strict_types=1);

use App\Domain\DocumentTemplate\AI\Rules\ValidSectionStructureRule;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

describe('ValidSectionStructureRule', function (): void {

    it('passes for structurally valid sections', function (): void {
        $issues = (new ValidSectionStructureRule)->validate([
            'sections' => [[
                'title' => 'Purpose',
                'content' => 'Purpose content.',
                'section_order' => 1,
                'section_type' => 'rich_text',
            ]],
        ], createValidationContext());

        expect($issues)->toBeEmpty();
    });

    it('reports a missing sections collection', function (): void {
        $issues = (new ValidSectionStructureRule)->validate(
            [],
            createValidationContext(),
        );

        expect($issues)->toHaveCount(1)
            ->and($issues->all()[0]->message())
            ->toBe('AI template generation returned invalid sections.')
            ->and($issues->all()[0]->path())
            ->toBe('sections');
    });

    it('reports missing and invalid section fields with paths', function (): void {
        $issues = (new ValidSectionStructureRule)->validate([
            'sections' => [[
                'content' => 123,
                'section_order' => 'first',
                'section_type' => '',
            ]],
        ], createValidationContext());

        expect($issues->isNotEmpty())->toBeTrue()
            ->and($issues->all()[0]->path())
            ->toBe('sections[0].title');
    });
});

function createValidationContext(): ValidationContext
{
    return new ValidationContext(
        'document_template',
    );
}
