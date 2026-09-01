<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domain\TMS\Services\DesignationTrainingAssignmentService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    public function saved(User $user): void
    {
        if ($user->designation_id === null) {
            return;
        }

        if (! $user->wasRecentlyCreated && ! $user->wasChanged('designation_id')) {
            return;
        }

        $actor = Auth::user();

        if (! $actor instanceof User) {
            $actor = $user;
        }

        app(DesignationTrainingAssignmentService::class)->syncForUser($user, $actor);
    }
}
