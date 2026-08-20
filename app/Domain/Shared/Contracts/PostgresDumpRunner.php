<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface PostgresDumpRunner
{
    public function dumpTo(string $absolutePath): void;
}
