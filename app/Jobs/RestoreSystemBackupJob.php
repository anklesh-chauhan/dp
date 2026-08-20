<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Shared\Services\SystemBackupService;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class RestoreSystemBackupJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public int $uniqueFor = 3600;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly string $uuid,
        public readonly ?int $actorId,
        public readonly string $reason,
    ) {}

    public function uniqueId(): string
    {
        return 'gxp-system-backup-restore';
    }

    public function handle(SystemBackupService $systemBackups): void
    {
        $systemBackups->restore($this->uuid, $this->actor(), $this->reason);
    }

    public function failed(?Throwable $exception): void
    {
        $this->notifyActor(
            $this->actor(),
            'Restore failed',
            $exception?->getMessage() ?? 'The system restore job failed.',
        );
    }

    private function actor(): ?User
    {
        if ($this->actorId === null) {
            return null;
        }

        return User::query()->find($this->actorId);
    }

    private function notifyActor(?User $actor, string $title, string $body): void
    {
        if (! $actor instanceof User) {
            return;
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->danger()
            ->sendToDatabase($actor);
    }
}
