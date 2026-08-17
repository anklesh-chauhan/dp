<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Models\CompetencyCurriculum;
use App\Domain\Shared\Contracts\CompetencyActionGate;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

final class CompetencyGate implements CompetencyActionGate
{
    public function __construct(private readonly CompetencyService $competencyService) {}

    /**
     * Fail open when no active curriculum is configured for the gate key.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function assert(User $user, string $gateKey): void
    {
        $curricula = CompetencyCurriculum::query()
            ->active()
            ->forGate($gateKey)
            ->get();

        if ($curricula->isEmpty()) {
            return;
        }

        foreach ($curricula as $curriculum) {
            $this->competencyService->assertCompetent($user, $curriculum);
        }
    }
}
