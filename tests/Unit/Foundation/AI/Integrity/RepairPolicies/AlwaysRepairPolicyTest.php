<?php

declare(strict_types=1);

use App\Foundation\AI\Integrity\RepairPolicies\AlwaysRepairPolicy;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

it('always requests repair', function (): void {
    $policy = new AlwaysRepairPolicy();

    $result = new ValidationResult();

    $context = new ValidationContext('generic_artifact');

    expect(
        $policy->shouldRepair(
            [],
            $result,
            $context,
        )
    )->toBeTrue();
});
