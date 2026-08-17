<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Models\User;

interface CalibrationReadinessGate
{
    /**
     * Fail open when no critical equipment assets exist, or when the gate is disabled.
     */
    public function assertNoOverdueCriticalCalibrations(User $actor): void;
}
