<?php

declare(strict_types=1);

use App\Domain\DMS\Services\DocumentNumberGeneratorService;
use App\Domain\DMS\Services\SopReferenceService;
use App\Domain\DMS\Services\VariableResolverService;
use App\Models\ControlledDocument;

it('resolves canonical and legacy generation collaborators through the DMS boundary', function (): void {
    $services = [
        App\Services\Sop\DocumentNumberGeneratorService::class => DocumentNumberGeneratorService::class,
        App\Services\Sop\SopReferenceService::class => SopReferenceService::class,
        App\Services\Sop\VariableResolverService::class => VariableResolverService::class,
    ];

    foreach ($services as $legacy => $canonical) {
        expect(app($canonical))->toBeInstanceOf($canonical)
            ->and(app($legacy))->toBeInstanceOf($canonical);
    }
});

it('preserves controlled-copy numbering and watermark behavior', function (): void {
    $document = new ControlledDocument([
        'document_number' => 'SOP-QA-00001',
    ]);

    $generator = app(DocumentNumberGeneratorService::class);

    expect($generator->generateIssuanceNumber($document, 3))->toBe('SOP-QA-00001-C03')
        ->and($generator->generateWatermarkCode($document, 3))->toBe('CC-SOPQA00001-03');
});

it('preserves variable substitution behavior', function (): void {
    $resolver = app(VariableResolverService::class);

    expect($resolver->replace(
        'Document {{ document.number }} remains {{ missing }}.',
        ['document' => ['number' => 'SOP-QA-00001']],
    ))->toBe('Document SOP-QA-00001 remains {{ missing }}.');
});
