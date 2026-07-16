<?php

declare(strict_types=1);

use App\Foundation\AI\Validation\Enums\ValidationSeverity;

it('identifies blocking severities', function (): void {
    expect(ValidationSeverity::INFO->isBlocking())->toBeFalse();
    expect(ValidationSeverity::WARNING->isBlocking())->toBeFalse();

    expect(ValidationSeverity::ERROR->isBlocking())->toBeTrue();
    expect(ValidationSeverity::CRITICAL->isBlocking())->toBeTrue();
});

it('returns severity priorities', function (): void {
    expect(ValidationSeverity::INFO->priority())->toBe(1);
    expect(ValidationSeverity::WARNING->priority())->toBe(2);
    expect(ValidationSeverity::ERROR->priority())->toBe(3);
    expect(ValidationSeverity::CRITICAL->priority())->toBe(4);
});
