<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\PostgresRestoreRunner;
use App\Domain\Shared\Exceptions\SystemBackupException;
use Illuminate\Support\Facades\Process;

final class ProcessPostgresRestoreRunner implements PostgresRestoreRunner
{
    public function __construct(private readonly PostgresClientBinaryLocator $binaries) {}

    public function restoreFrom(string $absolutePath): void
    {
        $connection = $this->pgsqlConnection();
        $binary = $this->binaries->resolve('pg_restore');

        $result = Process::timeout(3600)
            ->env(['PGPASSWORD' => (string) ($connection['password'] ?? '')])
            ->run([
                $binary,
                '--clean',
                '--if-exists',
                '--no-owner',
                '--no-acl',
                '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
                '--port='.(string) ($connection['port'] ?? '5432'),
                '--username='.(string) ($connection['username'] ?? 'postgres'),
                '--dbname='.(string) ($connection['database'] ?? ''),
                $absolutePath,
            ]);

        if (! $result->successful()) {
            throw new SystemBackupException(
                'PostgreSQL restore failed: '.trim($result->errorOutput().' '.$result->output()),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function pgsqlConnection(): array
    {
        $name = (string) config('database.default');
        $connection = config("database.connections.{$name}");

        if (! is_array($connection) || ($connection['driver'] ?? null) !== 'pgsql') {
            throw new SystemBackupException('System restore requires a PostgreSQL database connection.');
        }

        return $connection;
    }
}
