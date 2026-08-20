<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Shared\Contracts\PostgresDumpRunner;

final class FakePostgresDumpRunner implements PostgresDumpRunner
{
    public function dumpTo(string $absolutePath): void
    {
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($absolutePath, "FAKE-DUMP\n");
    }
}
