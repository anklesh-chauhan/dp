<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Enums\RuleCategory;

it('defines the expected rule categories', function (): void {
    expect(RuleCategory::cases())
        ->toHaveCount(7)
        ->and(RuleCategory::STRUCTURE->value)->toBe('structure')
        ->and(RuleCategory::DATA->value)->toBe('data')
        ->and(RuleCategory::FORMAT->value)->toBe('format')
        ->and(RuleCategory::CONSISTENCY->value)->toBe('consistency')
        ->and(RuleCategory::RELATIONSHIP->value)->toBe('relationship')
        ->and(RuleCategory::SECURITY->value)->toBe('security')
        ->and(RuleCategory::CUSTOM->value)->toBe('custom');
});
