<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\PostgresDumpRunner;
use App\Domain\Shared\Contracts\PostgresRestoreRunner;
use App\Domain\Shared\Enums\SecurityAuditEventType;
use App\Domain\Shared\Exceptions\SystemBackupException;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class SystemBackupService
{
    public function __construct(
        private readonly PostgresDumpRunner $dumpRunner,
        private readonly PostgresRestoreRunner $restoreRunner,
        private readonly SystemBackupFilePacker $filePacker,
        private readonly SecurityAuditRecorder $securityAuditRecorder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function create(?User $actor, string $reason): array
    {
        $uuid = (string) Str::uuid();
        $disk = Storage::disk('backups');
        $disk->makeDirectory($uuid);

        $dumpRelative = $uuid.'/database.dump';
        $filesRelative = $uuid.'/files.zip';
        $manifestRelative = $uuid.'/manifest.json';
        $dumpPath = $disk->path($dumpRelative);
        $filesPath = $disk->path($filesRelative);

        try {
            $this->dumpRunner->dumpTo($dumpPath);
            $this->filePacker->pack($this->fileDisks(), $filesPath);

            $manifest = [
                'backup_uuid' => $uuid,
                'app_name' => (string) config('app.name'),
                'created_at' => now()->utc()->toIso8601String(),
                'created_by' => $actor?->email,
                'reason' => $reason,
                'database' => (string) config('database.default'),
                'files_disks' => $this->fileDisks(),
                'database_bytes' => is_file($dumpPath) ? (int) filesize($dumpPath) : 0,
                'files_bytes' => is_file($filesPath) ? (int) filesize($filesPath) : 0,
                'database_sha256' => $this->hashFile($dumpPath),
                'files_sha256' => $this->hashFile($filesPath),
            ];

            $disk->put($manifestRelative, json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            $this->securityAuditRecorder->record(
                type: SecurityAuditEventType::BackupCreated,
                actor: $actor,
                reason: $reason,
                context: [
                    'backup_uuid' => $uuid,
                    'database_sha256' => $manifest['database_sha256'],
                    'files_sha256' => $manifest['files_sha256'],
                ],
            );

            $this->prune();

            return $manifest;
        } catch (Throwable $exception) {
            $disk->deleteDirectory($uuid);

            throw $exception;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $archives = [];

        foreach (Storage::disk('backups')->directories() as $directory) {
            $manifest = $this->readManifest($directory);

            if ($manifest === null) {
                continue;
            }

            $archives[] = $manifest;
        }

        usort($archives, function (array $left, array $right): int {
            return strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''));
        });

        return $archives;
    }

    /**
     * @return array<string, mixed>
     */
    public function manifest(string $uuid): array
    {
        $manifest = $this->readManifest($uuid);

        if ($manifest === null) {
            throw new SystemBackupException('The requested backup archive was not found.');
        }

        return $manifest;
    }

    public function verify(string $uuid): void
    {
        $manifest = $this->manifest($uuid);
        $disk = Storage::disk('backups');
        $dumpPath = $disk->path($uuid.'/database.dump');
        $filesPath = $disk->path($uuid.'/files.zip');

        if (! is_file($dumpPath) || ! is_file($filesPath)) {
            throw new SystemBackupException('The backup archive is incomplete.');
        }

        if (! hash_equals((string) $manifest['database_sha256'], $this->hashFile($dumpPath))) {
            throw new SystemBackupException('The database dump checksum does not match the backup manifest.');
        }

        if (! hash_equals((string) $manifest['files_sha256'], $this->hashFile($filesPath))) {
            throw new SystemBackupException('The file archive checksum does not match the backup manifest.');
        }
    }

    public function restore(string $uuid, ?User $actor, string $reason): void
    {
        $manifest = $this->manifest($uuid);

        try {
            $this->verify($uuid);
        } catch (SystemBackupException $exception) {
            $this->recordRestoreFailure($actor, $reason, $uuid, $exception->getMessage());
            $this->appendRestoreLog($uuid, $actor, $reason, 'failed', $exception->getMessage());

            throw $exception;
        }

        $disk = Storage::disk('backups');
        $dumpPath = $disk->path($uuid.'/database.dump');
        $filesPath = $disk->path($uuid.'/files.zip');
        $fileDisks = $this->fileDisksFromManifest($manifest);

        try {
            $this->restoreRunner->restoreFrom($dumpPath);
            $this->filePacker->unpack($filesPath, $fileDisks);
            $this->appendRestoreLog($uuid, $actor, $reason, 'restored');
        } catch (Throwable $exception) {
            $this->appendRestoreLog($uuid, $actor, $reason, 'failed', $exception->getMessage());
            $this->recordRestoreFailure($actor, $reason, $uuid, $exception->getMessage());

            throw $exception instanceof SystemBackupException
                ? $exception
                : new SystemBackupException($exception->getMessage(), 0, $exception);
        }

        $this->securityAuditRecorder->record(
            type: SecurityAuditEventType::BackupRestored,
            actor: $actor,
            reason: $reason,
            context: [
                'backup_uuid' => $uuid,
                'database_sha256' => $manifest['database_sha256'] ?? null,
                'files_sha256' => $manifest['files_sha256'] ?? null,
            ],
        );
    }

    public function absolutePath(string $uuid, string $filename): string
    {
        $this->manifest($uuid);

        $path = Storage::disk('backups')->path($uuid.'/'.$filename);

        if (! is_file($path)) {
            throw new SystemBackupException('The requested backup artifact was not found.');
        }

        return $path;
    }

    public function writeDownloadZip(string $uuid, string $absoluteZipPath, ?User $actor = null): void
    {
        $this->verify($uuid);

        $zip = new \ZipArchive;

        if ($zip->open($absoluteZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new SystemBackupException('Unable to create a downloadable backup archive.');
        }

        $directory = Storage::disk('backups')->path($uuid);

        foreach (['manifest.json', 'database.dump', 'files.zip'] as $filename) {
            $zip->addFile($directory.DIRECTORY_SEPARATOR.$filename, $filename);
        }

        $zip->close();

        $this->securityAuditRecorder->record(
            type: SecurityAuditEventType::BackupDownloaded,
            actor: $actor,
            reason: 'Backup archive downloaded.',
            context: ['backup_uuid' => $uuid],
        );
    }

    public function prune(): void
    {
        $retain = max(1, (int) config('gxp.backup.retain_count', 12));
        $archives = $this->list();

        foreach (array_slice($archives, $retain) as $archive) {
            $uuid = (string) ($archive['backup_uuid'] ?? '');

            if ($uuid === '') {
                continue;
            }

            Storage::disk('backups')->deleteDirectory($uuid);
        }
    }

    /**
     * @return list<string>
     */
    private function fileDisks(): array
    {
        $disks = config('gxp.backup.disks', ['local', 'public']);

        if (! is_array($disks)) {
            return ['local', 'public'];
        }

        return array_values(array_filter(
            $disks,
            fn (mixed $disk): bool => is_string($disk) && $disk !== '' && $disk !== 'backups',
        ));
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private function fileDisksFromManifest(array $manifest): array
    {
        $disks = $manifest['files_disks'] ?? $this->fileDisks();

        if (! is_array($disks)) {
            return $this->fileDisks();
        }

        return array_values(array_filter(
            $disks,
            fn (mixed $disk): bool => is_string($disk) && $disk !== '' && $disk !== 'backups',
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readManifest(string $uuid): ?array
    {
        $path = $uuid.'/manifest.json';

        if (Storage::disk('backups')->missing($path)) {
            return null;
        }

        $decoded = json_decode((string) Storage::disk('backups')->get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function hashFile(string $absolutePath): string
    {
        $hash = hash_file('sha256', $absolutePath);

        if (! is_string($hash) || $hash === '') {
            throw new SystemBackupException('Unable to checksum a backup artifact.');
        }

        return $hash;
    }

    private function appendRestoreLog(
        string $uuid,
        ?User $actor,
        string $reason,
        string $outcome,
        ?string $error = null,
    ): void {
        $line = json_encode([
            'occurred_at' => now()->utc()->toIso8601String(),
            'backup_uuid' => $uuid,
            'actor_email' => $actor?->email,
            'reason' => $reason,
            'outcome' => $outcome,
            'error' => $error,
        ], JSON_THROW_ON_ERROR);

        Storage::disk('backups')->append('restore.log', $line.PHP_EOL);
    }

    private function recordRestoreFailure(?User $actor, string $reason, string $uuid, string $error): void
    {
        $this->securityAuditRecorder->record(
            type: SecurityAuditEventType::BackupRestoreFailed,
            actor: $actor,
            reason: $reason,
            context: [
                'backup_uuid' => $uuid,
                'error' => $error,
            ],
        );
    }
}
