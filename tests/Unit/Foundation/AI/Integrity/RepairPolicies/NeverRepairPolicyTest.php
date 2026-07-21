<?php

declare(strict_types=1);

use App\Foundation\AI\Integrity\RepairPolicies\NeverRepairPolicy;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

it('never requests repair', function (): void {
    $policy = new NeverRepairPolicy();

    $result = new ValidationResult();

    $context = new ValidationContext('generic_artifact');

    expect(
        $policy->shouldRepair(
            [],
            $result,
            $context,
        )
    )->toBeFalse();
});
