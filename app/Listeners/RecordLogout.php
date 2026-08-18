<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Shared\Services\UserAccessService;
use App\Models\User;
use Illuminate\Auth\Events\Logout;

class RecordLogout
{
    public function __construct(private readonly UserAccessService $userAccessService) {}

    public function handle(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->userAccessService->recordLogout($event->user);
        }
    }
}
