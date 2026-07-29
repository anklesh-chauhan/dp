<?php

declare(strict_types=1);

use App\Http\Controllers\ControlledDocumentPrintController;
use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\IssuanceStatus;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

it('generates an issued-copy print URL with the controlled document route key', function (): void {
    expect(route('controlled-documents.print', [
        'controlledDocument' => 123,
        'issuance' => 456,
    ], false))->toBe('/controlled-documents/123/print?issuance=456');
});

it('allows direct print only for approved or effective non-issuable documents', function (): void {
    $approvedDocument = documentForPrinting(
        isIssuable: false,
        status: DocumentStatus::APPROVED,
    );
    $effectiveDocument = documentForPrinting(
        isIssuable: false,
        status: DocumentStatus::EFFECTIVE,
    );
    $draftDocument = documentForPrinting(
        isIssuable: false,
        status: DocumentStatus::DRAFT,
    );
    $controlledDocument = documentForPrinting(
        isIssuable: true,
        status: DocumentStatus::EFFECTIVE,
    );

    expect($approvedDocument->canBePrintedDirectly())->toBeTrue()
        ->and($effectiveDocument->canBePrintedDirectly())->toBeTrue()
        ->and($draftDocument->canBePrintedDirectly())->toBeFalse()
        ->and($controlledDocument->canBePrintedDirectly())->toBeFalse();
});

it('allows an effective controlled document to print only through its active issuance', function (): void {
    $document = documentForPrinting(
        isIssuable: true,
        status: DocumentStatus::EFFECTIVE,
    );
    $activeIssuance = issuanceForPrinting($document, IssuanceStatus::ACTIVE);
    $recalledIssuance = issuanceForPrinting($document, IssuanceStatus::RECALLED);

    expect($document->canBePrinted())->toBeFalse()
        ->and($document->canBePrinted($activeIssuance))->toBeTrue()
        ->and($document->canBePrinted($recalledIssuance))->toBeFalse();
});

it('guides direct controlled-copy print attempts to the issuance register', function (): void {
    $document = documentForPrinting(
        isIssuable: true,
        status: DocumentStatus::EFFECTIVE,
    );

    expect(fn () => app(ControlledDocumentPrintController::class)(
        Request::create("/controlled-documents/{$document->id}/print"),
        $document,
    ))->toThrow(
        AccessDeniedHttpException::class,
        'Open DMS → Issuance → Log Documents',
    );
});

function documentForPrinting(bool $isIssuable, string $status): ControlledDocument
{
    $document = new ControlledDocument;
    $document->forceFill(['id' => fake()->unique()->numberBetween(1, 10_000)]);
    $document->setRelation('documentType', new DocumentType([
        'is_issuable' => $isIssuable,
    ]));
    $document->setRelation('documentStatus', new DocumentStatus([
        'code' => $status,
        'name' => ucfirst(str_replace('_', ' ', $status)),
    ]));

    return $document;
}

function issuanceForPrinting(ControlledDocument $document, string $status): DocumentIssuance
{
    $issuance = new DocumentIssuance(['document_id' => $document->id]);
    $issuance->setRelation('issuanceStatus', new IssuanceStatus([
        'code' => $status,
        'name' => ucfirst($status),
    ]));

    return $issuance;
}
