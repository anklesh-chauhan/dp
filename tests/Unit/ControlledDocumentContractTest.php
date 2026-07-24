<?php

declare(strict_types=1);

use App\Domain\DMS\Actions\CreateDocumentRevisionAction;
use App\Domain\DMS\Contracts\ControlledDocument;
use App\Domain\DMS\Services\DocumentRevisionService;
use App\Models\SopDocument;

it('adapts legacy SOP documents to the DMS controlled document contract', function (): void {
    $document = new SopDocument([
        'document_number' => 'SOP-QA-00001',
        'title' => 'Deviation Management Procedure',
        'version' => 3,
    ]);

    expect($document)
        ->toBeInstanceOf(ControlledDocument::class)
        ->and($document->controlledDocumentReference())->toBe('SOP-QA-00001')
        ->and($document->controlledDocumentTitle())->toBe('Deviation Management Procedure')
        ->and($document->controlledDocumentVersion())->toBe(3);
});

arch('DMS contracts are interfaces')
    ->expect('App\Domain\DMS\Contracts')
    ->toBeInterfaces();

arch('DMS contracts do not depend on optional modules')
    ->expect('App\Domain\DMS\Contracts')
    ->not->toUse([
        'App\Domain\AI',
        'App\Domain\QMS',
        'App\Foundation\AI',
        'App\Services\AI',
    ]);

arch('DMS actions and services are classes')
    ->expect([
        'App\Domain\DMS\Actions',
        'App\Domain\DMS\Services',
    ])
    ->toBeClasses();

it('keeps legacy revision entry points compatible with the DMS domain classes', function (): void {
    expect(is_subclass_of(
        App\Actions\Sop\CreateDocumentRevisionAction::class,
        CreateDocumentRevisionAction::class,
    ))->toBeTrue()
        ->and(is_subclass_of(
            App\Services\Sop\DocumentRevisionService::class,
            DocumentRevisionService::class,
        ))->toBeTrue();
});

arch('DMS domain code does not depend on optional modules')
    ->expect('App\Domain\DMS')
    ->not->toUse([
        'App\Domain\AI',
        'App\Domain\QMS',
        'App\Foundation\AI',
        'App\Services\AI',
    ]);
