<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Shared\Services\UserAccessService;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordSuccessfulLogin
{
    public function __construct(private readonly UserAccessService $userAccessService) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->userAccessService->recordSuccessfulLogin($event->user);
        }
    }
}
