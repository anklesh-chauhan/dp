<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\Exceptions\SystemBackupException;
use App\Domain\Shared\Services\SystemBackupService;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gxp:backup-restore {uuid} {--reason=} {--actor= : Signed panel actor id} {--force : Skip signature; local and testing only}')]
#[Description('Restore an integrity-checked system backup over this instance.')]
class RestoreSystemBackupCommand extends Command
{
    public function handle(SystemBackupService $systemBackups): int
    {
        $reason = trim((string) $this->option('reason'));

        if ($reason === '') {
            $this->error('A reason is required to restore a system backup.');

            return self::FAILURE;
        }

        $actor = $this->resolveActor();

        if ($actor === false) {
            return self::FAILURE;
        }

        try {
            $systemBackups->restore((string) $this->argument('uuid'), $actor, $reason);
        } catch (SystemBackupException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Restored backup '.$this->argument('uuid'));

        return self::SUCCESS;
    }

    private function resolveActor(): User|false|null
    {
        if ($this->option('force')) {
            if (! app()->environment(['local', 'testing'])) {
                $this->error('Unsigned --force restore is only available in local and testing environments.');

                return false;
            }

            return null;
        }

        $actorId = $this->option('actor');

        if (! filled($actorId)) {
            $this->error('Restore from the System backup page with an electronic signature, or use --force in local/testing.');

            return false;
        }

        $actor = User::query()->find($actorId);

        if (! $actor instanceof User) {
            $this->error('The restore actor was not found.');

            return false;
        }

        return $actor;
    }
}
