<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Filament\Support\ServiceExceptionHandler;

trait HandlesServiceExceptions
{
    /**
     * @param  callable(): mixed  $callback
     * @param  callable(mixed): void|null  $afterSuccess
     */
    protected function runServiceAction(
        callable $callback,
        ?string $failureTitle = null,
        ?string $successTitle = null,
        ?callable $afterSuccess = null,
        ?string $successBody = null,
    ): mixed {
        return ServiceExceptionHandler::run(
            $callback,
            $failureTitle,
            $successTitle,
            $afterSuccess,
            $successBody,
        );
    }
}
