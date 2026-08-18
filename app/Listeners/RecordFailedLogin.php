<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Shared\Services\UserAccessService;
use App\Models\User;
use Illuminate\Auth\Events\Failed;

class RecordFailedLogin
{
    public function __construct(private readonly UserAccessService $userAccessService) {}

    public function handle(Failed $event): void
    {
        $email = is_string($event->credentials['email'] ?? null)
            ? $event->credentials['email']
            : null;

        $user = $event->user instanceof User
            ? $event->user
            : (filled($email) ? User::query()->where('email', $email)->first() : null);

        $this->userAccessService->recordFailedLogin($user, $email);
    }
}
