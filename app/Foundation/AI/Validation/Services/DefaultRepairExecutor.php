<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Services;

use App\Foundation\AI\Validation\Contracts\RepairExecutor;
use App\Foundation\AI\Validation\Contracts\RepairStrategy;
use App\Foundation\AI\Validation\Exceptions\RepairFailedException;
use App\Foundation\AI\Validation\ValueObjects\RepairResult;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;
use Throwable;

final readonly class DefaultRepairExecutor implements RepairExecutor
{
    public function execute(
        RepairStrategy $strategy,
        mixed $artifact,
        ValidationResult $validationResult,
        ValidationContext $context,
    ): RepairResult {
        if (! $strategy->supports($artifact, $validationResult, $context)) {
            return new RepairResult(
                artifact: $artifact,
                successful: false,
                modified: false,
                metadata: [
                    'reason' => 'strategy_not_supported',
                ],
            );
        }

        try {
            return $strategy->repair(
                artifact: $artifact,
                validationResult: $validationResult,
                context: $context,
            );
        } catch (Throwable $exception) {
            throw new RepairFailedException(
                'Repair strategy execution failed.',
                previous: $exception,
            );
        }
    }
}
