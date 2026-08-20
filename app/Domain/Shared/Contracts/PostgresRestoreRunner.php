<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface PostgresRestoreRunner
{
    public function restoreFrom(string $absolutePath): void;
}
