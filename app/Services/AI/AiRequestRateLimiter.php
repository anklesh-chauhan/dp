<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class AiRequestRateLimiter
{
    public function ensureAvailable(
        string $feature,
        User $user,
        int $maxAttempts,
        int $decaySeconds = 60,
    ): void {
        $key = "ai:{$feature}:user:{$user->getKey()}";

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                'userMessage' => 'The AI request limit has been reached. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
