<?php

declare(strict_types=1);

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Models\ControlledDocument;

it('adapts a controlled document to the Shared approvable subject contract', function (): void {
    $document = new ControlledDocument([
        'document_number' => 'SOP-QA-00001',
        'title' => 'Deviation Management Procedure',
        'department_id' => 7,
        'created_by' => 11,
        'owner_id' => 13,
    ]);
    $document->setAttribute('id', 42);

    expect($document)
        ->toBeInstanceOf(ApprovableSubject::class)
        ->and($document->approvalSubjectKey())->toBe(42)
        ->and($document->approvalSubjectReference())->toBe('SOP-QA-00001')
        ->and($document->approvalSubjectTitle())->toBe('Deviation Management Procedure')
        ->and($document->approvalSubjectDepartmentId())->toBe(7)
        ->and($document->approvalSubjectCreatedById())->toBe(11)
        ->and($document->approvalSubjectOwnerId())->toBe(13);
});

arch('Shared contracts are interfaces')
    ->expect('App\Domain\Shared\Contracts')
    ->toBeInterfaces();

arch('Shared contracts do not depend on product modules')
    ->expect('App\Domain\Shared\Contracts')
    ->not->toUse([
        'App\Domain\AI',
        'App\Domain\DMS',
        'App\Domain\QMS',
        'App\Foundation\AI',
        'App\Services\AI',
        'App\Services\Sop',
    ]);
