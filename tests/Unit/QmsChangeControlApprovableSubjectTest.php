<?php

declare(strict_types=1);

use App\Domain\QMS\Models\ChangeControl;
use App\Domain\Shared\Contracts\ApprovableSubject;

it('adapts a change control to the Shared approvable subject contract', function (): void {
    $changeControl = new ChangeControl([
        'change_number' => 'CC-00042',
        'title' => 'Replace purified water loop pump',
        'department_id' => 7,
        'requested_by' => 11,
        'owner_id' => 13,
    ]);
    $changeControl->setAttribute('id', 42);

    expect($changeControl)
        ->toBeInstanceOf(ApprovableSubject::class)
        ->and($changeControl->approvalSubjectKey())->toBe(42)
        ->and($changeControl->approvalSubjectReference())->toBe('CC-00042')
        ->and($changeControl->approvalSubjectTitle())->toBe('Replace purified water loop pump')
        ->and($changeControl->approvalSubjectDepartmentId())->toBe(7)
        ->and($changeControl->approvalSubjectCreatedById())->toBe(11)
        ->and($changeControl->approvalSubjectOwnerId())->toBe(13);
});
