<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface ContentIntegrityHasher
{
    public function hash(string $disk, string $path): ?string;
}
