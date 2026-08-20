<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Shared\Contracts\PostgresRestoreRunner;
use Throwable;

final class FakePostgresRestoreRunner implements PostgresRestoreRunner
{
    public bool $called = false;

    public ?string $path = null;

    public ?Throwable $throw = null;

    public function restoreFrom(string $absolutePath): void
    {
        if ($this->throw instanceof Throwable) {
            throw $this->throw;
        }

        $this->called = true;
        $this->path = $absolutePath;
    }
}
