<?php

declare(strict_types=1);

use App\Domain\DMS\Services\DocumentActivationService;

it('resolves canonical and legacy activation services through the DMS boundary', function (): void {
    expect(app(DocumentActivationService::class))->toBeInstanceOf(DocumentActivationService::class)
        ->and(app(App\Services\Sop\DocumentActivationService::class))
        ->toBeInstanceOf(DocumentActivationService::class);
});
