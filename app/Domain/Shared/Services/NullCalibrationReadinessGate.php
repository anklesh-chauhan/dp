<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\CalibrationReadinessGate;
use App\Models\User;

final class NullCalibrationReadinessGate implements CalibrationReadinessGate
{
    public function assertNoOverdueCriticalCalibrations(User $actor): void {}
}
