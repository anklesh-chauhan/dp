<?php

declare(strict_types=1);

use App\Domain\SopTemplate\AI\Support\PlaceholderExtractor;

describe('PlaceholderExtractor', function (): void {

    it('extracts a single placeholder', function (): void {
        $extractor = new PlaceholderExtractor();

        expect(
            $extractor->extract(
                'Effective date: {{effective_date}}'
            ),
        )->toBe([
            'effective_date',
        ]);
    });

    it('extracts multiple placeholders', function (): void {
        $extractor = new PlaceholderExtractor();

        expect(
            $extractor->extract(
                '{{effective_date}} {{revision_no}}'
            ),
        )->toBe([
            'effective_date',
            'revision_no',
        ]);
    });

    it('returns unique placeholders', function (): void {
        $extractor = new PlaceholderExtractor();

        expect(
            $extractor->extract(
                '{{effective_date}} {{effective_date}}'
            ),
        )->toBe([
            'effective_date',
        ]);
    });

    it('supports whitespace inside placeholders', function (): void {
        $extractor = new PlaceholderExtractor();

        expect(
            $extractor->extract(
                '{{ effective_date }}'
            ),
        )->toBe([
            'effective_date',
        ]);
    });

    it('returns an empty array when no placeholders exist', function (): void {
        $extractor = new PlaceholderExtractor();

        expect(
            $extractor->extract(
                'No placeholders here.'
            ),
        )->toBe([]);
    });

    it('ignores malformed placeholders', function (): void {
        $extractor = new PlaceholderExtractor();

        expect(
            $extractor->extract(
                '{{123invalid}} {{}} {{ invalid-name }}'
            ),
        )->toBe([]);
    });
});
