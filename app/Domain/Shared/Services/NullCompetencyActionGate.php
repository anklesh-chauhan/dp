<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\CompetencyActionGate;
use App\Models\User;

final class NullCompetencyActionGate implements CompetencyActionGate
{
    public function assert(User $user, string $gateKey): void {}
}
