<?php

declare(strict_types=1);

namespace App\Foundation\AI\Validation\Pipeline;

use App\Foundation\AI\Validation\Contracts\RepairExecutor;
use App\Foundation\AI\Validation\Contracts\RepairStrategy;
use App\Foundation\AI\Validation\Contracts\RepairPipeline;
use App\Foundation\AI\Validation\ValueObjects\RepairResult;
use App\Foundation\AI\Validation\ValueObjects\ValidationContext;
use App\Foundation\AI\Validation\ValueObjects\ValidationResult;

final readonly class DefaultRepairPipeline implements RepairPipeline
{
    /**
     * @param iterable<RepairStrategy> $strategies
     */
    public function __construct(
        private iterable $strategies,
        private RepairExecutor $executor,
    ) {
    }

    public function repair(
        mixed $artifact,
        ValidationResult $validationResult,
        ValidationContext $context,
    ): RepairResult {
        foreach ($this->strategies as $strategy) {
            $result = $this->executor->execute(
                strategy: $strategy,
                artifact: $artifact,
                validationResult: $validationResult,
                context: $context,
            );

            if ($result->isSuccessful()) {
                return $result;
            }
        }

        return new RepairResult(
            artifact: $artifact,
            successful: false,
            modified: false,
            metadata: [
                'reason' => 'no_strategy_succeeded',
            ],
        );
    }
}
