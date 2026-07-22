<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Enums\RuleExecutionMode;

it('defines the expected execution modes', function (): void {
    expect(RuleExecutionMode::cases())
        ->toHaveCount(3)
        ->and(RuleExecutionMode::SEQUENTIAL->value)->toBe('sequential')
        ->and(RuleExecutionMode::PARALLEL->value)->toBe('parallel')
        ->and(RuleExecutionMode::EXPENSIVE->value)->toBe('expensive');
});
