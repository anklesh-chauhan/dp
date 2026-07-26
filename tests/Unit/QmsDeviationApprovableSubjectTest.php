<?php

declare(strict_types=1);

use App\Domain\QMS\Models\Deviation;
use App\Domain\Shared\Contracts\ApprovableSubject;

it('adapts a deviation to the Shared approvable subject contract', function (): void {
    $deviation = new Deviation([
        'deviation_number' => 'DEV-2026-00042',
        'title' => 'Temperature excursion in finished goods storage',
        'department_id' => 7,
        'reported_by' => 11,
        'owner_id' => 13,
    ]);
    $deviation->setAttribute('id', 42);

    expect($deviation)
        ->toBeInstanceOf(ApprovableSubject::class)
        ->and($deviation->approvalSubjectKey())->toBe(42)
        ->and($deviation->approvalSubjectReference())->toBe('DEV-2026-00042')
        ->and($deviation->approvalSubjectTitle())->toBe('Temperature excursion in finished goods storage')
        ->and($deviation->approvalSubjectDepartmentId())->toBe(7)
        ->and($deviation->approvalSubjectCreatedById())->toBe(11)
        ->and($deviation->approvalSubjectOwnerId())->toBe(13);
});
