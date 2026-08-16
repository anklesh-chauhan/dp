<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class WorkflowNotificationDispatcher
{
    /**
     * @param  iterable<User>  $recipients
     */
    public function send(iterable $recipients, User $actor, Notification $notification): void
    {
        $users = collect($recipients)
            ->filter(fn (mixed $user): bool => $user instanceof User)
            ->unique(fn (User $user): int => (int) $user->getKey())
            ->reject(fn (User $user): bool => (int) $user->getKey() === (int) $actor->getKey())
            ->values();

        if ($users->isEmpty()) {
            return;
        }

        $dispatch = function () use ($notification, $users): void {
            $notification->sendToDatabase($users);
        };

        if (app()->runningUnitTests()) {
            $dispatch();

            return;
        }

        DB::afterCommit($dispatch);
    }

    /**
     * @return list<Action>
     */
    public function openActions(string $url, string $label = 'Open'): array
    {
        return [
            Action::make('open')
                ->label($label)
                ->button()
                ->url($url)
                ->markAsRead(),
        ];
    }
}
