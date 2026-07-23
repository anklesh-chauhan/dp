<?php

declare(strict_types=1);

use App\Domain\SopTemplate\AI\Support\GeneratedTemplateAnalysis;
use App\Domain\SopTemplate\AI\Support\PlaceholderExtractor;

describe('GeneratedTemplateAnalysis', function (): void {

    function validGeneratedTemplate(): array
    {
        return [
            'sections' => [
                [
                    'title' => 'Purpose',
                    'content' => 'Effective Date: {{effective_date}}',
                    'section_order' => 1,
                    'section_type' => 'rich_text',
                ],
                [
                    'title' => 'Scope',
                    'content' => 'Revision: {{revision_no}} {{effective_date}}',
                    'section_order' => 2,
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
                    'name' => 'revision_no',
                    'label' => 'Revision No',
                    'datatype' => 'text',
                    'default_value' => '',
                    'required' => true,
                ],
            ],
        ];
    }

    it('analyzes a valid generated template', function (): void {
        $analysis = GeneratedTemplateAnalysis::analyze(
            validGeneratedTemplate(),
            new PlaceholderExtractor(),
        );

        expect($analysis->sections())->toHaveCount(2)
            ->and($analysis->variables())->toHaveCount(2)
            ->and($analysis->variableNames())->toBe([
                'effective_date',
                'revision_no',
            ])
            ->and($analysis->placeholderNames())->toBe([
                'effective_date',
                'revision_no',
            ]);
    });

    it('extracts all variable names', function (): void {
        $analysis = GeneratedTemplateAnalysis::analyze(
            validGeneratedTemplate(),
            new PlaceholderExtractor(),
        );

        expect($analysis->variableNames())
            ->toBe([
                'effective_date',
                'revision_no',
            ]);
    });

    it('extracts all placeholder names', function (): void {
        $analysis = GeneratedTemplateAnalysis::analyze(
            validGeneratedTemplate(),
            new PlaceholderExtractor(),
        );

        expect($analysis->placeholderNames())
            ->toBe([
                'effective_date',
                'revision_no',
            ]);
    });

    it('removes duplicate placeholders', function (): void {
        $template = validGeneratedTemplate();

        $template['sections'][1]['content'] =
            '{{effective_date}} {{effective_date}} {{revision_no}}';

        $analysis = GeneratedTemplateAnalysis::analyze(
            $template,
            new PlaceholderExtractor(),
        );

        expect($analysis->placeholderNames())
            ->toBe([
                'effective_date',
                'revision_no',
            ]);
    });

    it('returns sections unchanged', function (): void {
        $template = validGeneratedTemplate();

        $analysis = GeneratedTemplateAnalysis::analyze(
            $template,
            new PlaceholderExtractor(),
        );

        expect($analysis->sections())
            ->toBe($template['sections']);
    });

    it('returns variables unchanged', function (): void {
        $template = validGeneratedTemplate();

        $analysis = GeneratedTemplateAnalysis::analyze(
            $template,
            new PlaceholderExtractor(),
        );

        expect($analysis->variables())
            ->toBe($template['variables']);
    });

    it('throws when sections are missing', function (): void {
        $template = validGeneratedTemplate();

        unset($template['sections']);

        expect(fn () => GeneratedTemplateAnalysis::analyze(
            $template,
            new PlaceholderExtractor(),
        ))
            ->toThrow(
                InvalidArgumentException::class,
                'Generated template sections must be an array.',
            );
    });

    it('throws when variables are missing', function (): void {
        $template = validGeneratedTemplate();

        unset($template['variables']);

        expect(fn () => GeneratedTemplateAnalysis::analyze(
            $template,
            new PlaceholderExtractor(),
        ))
            ->toThrow(
                InvalidArgumentException::class,
                'Generated template variables must be an array.',
            );
    });

    it('throws when a variable name is missing', function (): void {
        $template = validGeneratedTemplate();

        unset($template['variables'][0]['name']);

        expect(fn () => GeneratedTemplateAnalysis::analyze(
            $template,
            new PlaceholderExtractor(),
        ))
            ->toThrow(
                InvalidArgumentException::class,
                'Generated variable at index [0] is missing its name.',
            );
    });

    it('throws when section content is not a string', function (): void {
        $template = validGeneratedTemplate();

        $template['sections'][0]['content'] = 123;

        expect(fn () => GeneratedTemplateAnalysis::analyze(
            $template,
            new PlaceholderExtractor(),
        ))
            ->toThrow(
                InvalidArgumentException::class,
                'Generated section content at index [0] must be a string.',
            );
    });
});
