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

final class CreateSystemBackupJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public int $uniqueFor = 3600;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly ?int $actorId,
        public readonly string $reason,
    ) {}

    public function uniqueId(): string
    {
        return 'gxp-system-backup-create';
    }

    public function handle(SystemBackupService $systemBackups): void
    {
        $actor = $this->actor();
        $manifest = $systemBackups->create($actor, $this->reason);

        $this->notifyActor(
            $actor,
            'Backup created',
            'Archive '.$manifest['backup_uuid'].' was written with SHA-256 checksums.',
            success: true,
        );
    }

    public function failed(?Throwable $exception): void
    {
        $this->notifyActor(
            $this->actor(),
            'Backup failed',
            $exception?->getMessage() ?? 'The system backup job failed.',
            success: false,
        );
    }

    private function actor(): ?User
    {
        if ($this->actorId === null) {
            return null;
        }

        return User::query()->find($this->actorId);
    }

    private function notifyActor(?User $actor, string $title, string $body, bool $success): void
    {
        if (! $actor instanceof User) {
            return;
        }

        $notification = Notification::make()
            ->title($title)
            ->body($body);

        if ($success) {
            $notification->success();
        } else {
            $notification->danger();
        }

        $notification->sendToDatabase($actor);
    }
}
