<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\Exceptions\SystemBackupException;
use App\Domain\Shared\Services\SystemBackupService;
use App\Jobs\CreateSystemBackupJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gxp:backup-create {--sync : Create the archive in this process instead of queueing} {--reason=Scheduled backup.}')]
#[Description('Queue an integrity-checked archive of the PostgreSQL database and GxP file disks.')]
class CreateSystemBackupCommand extends Command
{
    public function handle(SystemBackupService $systemBackups): int
    {
        $reason = trim((string) $this->option('reason'));

        if ($reason === '') {
            $reason = 'Scheduled backup.';
        }

        if (! $this->option('sync')) {
            CreateSystemBackupJob::dispatch(null, $reason);
            $this->info('Queued a system backup.');

            return self::SUCCESS;
        }

        try {
            $manifest = $systemBackups->create(null, $reason);
        } catch (SystemBackupException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Created backup '.$manifest['backup_uuid']);

        return self::SUCCESS;
    }
}
