<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Models\User;

interface CompetencyActionGate
{
    /**
     * Fail open when no active curriculum is configured for the gate key.
     */
    public function assert(User $user, string $gateKey): void;
}
