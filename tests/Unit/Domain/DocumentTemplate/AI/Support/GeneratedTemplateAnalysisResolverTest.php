<?php

declare(strict_types=1);

use App\Domain\DocumentTemplate\AI\Support\GeneratedTemplateAnalysis;
use App\Domain\DocumentTemplate\AI\Support\GeneratedTemplateAnalysisResolver;
use App\Domain\DocumentTemplate\AI\Support\PlaceholderExtractor;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;

describe('GeneratedTemplateAnalysisResolver', function (): void {

    it('returns the analysis from the validation context', function (): void {
        $analysis = GeneratedTemplateAnalysis::analyze(
            [
                'sections' => [],
                'variables' => [],
            ],
            new PlaceholderExtractor,
        );

        $context = new ValidationContext(
            artifactType: 'document_template',
            attributes: [
                'analysis' => $analysis,
            ],
        );

        $resolver = new GeneratedTemplateAnalysisResolver;

        expect(
            $resolver->resolve($context),
        )->toBe($analysis);
    });

    it('throws when the analysis is missing', function (): void {
        $resolver = new GeneratedTemplateAnalysisResolver;

        $context = new ValidationContext(
            artifactType: 'document_template',
        );

        expect(
            fn () => $resolver->resolve($context),
        )->toThrow(
            RuntimeException::class,
            'Generated template analysis is missing from the validation context.',
        );
    });

    it('throws when the analysis has an invalid type', function (): void {
        $resolver = new GeneratedTemplateAnalysisResolver;

        $context = new ValidationContext(
            artifactType: 'document_template',
            attributes: [
                'analysis' => 'invalid',
            ],
        );

        expect(
            fn () => $resolver->resolve($context),
        )->toThrow(
            RuntimeException::class,
            'Generated template analysis is missing from the validation context.',
        );
    });
});
