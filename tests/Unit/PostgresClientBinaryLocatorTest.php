<?php

declare(strict_types=1);

use App\Domain\Shared\Exceptions\SystemBackupException;
use App\Domain\Shared\Services\PostgresClientBinaryLocator;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->binDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gxp-pgsql-bin-'.bin2hex(random_bytes(4));
    mkdir($this->binDir, 0755, true);
    $this->executable = PHP_OS_FAMILY === 'Windows' ? 'pg_dump.exe' : 'pg_dump';
    file_put_contents($this->binDir.DIRECTORY_SEPARATOR.$this->executable, '');
});

afterEach(function (): void {
    if (isset($this->binDir) && is_dir($this->binDir)) {
        File::deleteDirectory($this->binDir);
    }
});

it('uses the configured PostgreSQL client directory', function (): void {
    config()->set('gxp.backup.pgsql_bin', $this->binDir);

    expect(app(PostgresClientBinaryLocator::class)->resolve('pg_dump'))
        ->toBe($this->binDir.DIRECTORY_SEPARATOR.$this->executable);
});

it('fails when the configured directory does not contain the client tools', function (): void {
    config()->set('gxp.backup.pgsql_bin', $this->binDir.DIRECTORY_SEPARATOR.'missing');

    expect(fn () => app(PostgresClientBinaryLocator::class)->resolve('pg_dump'))
        ->toThrow(SystemBackupException::class, 'GXP_BACKUP_PGSQL_BIN');
});

it('finds client tools on PATH when the bin directory is not configured', function (): void {
    config()->set('gxp.backup.pgsql_bin', null);
    $previousPath = getenv('PATH');
    $separator = PHP_OS_FAMILY === 'Windows' ? ';' : ':';
    putenv('PATH='.$this->binDir.$separator.(is_string($previousPath) ? $previousPath : ''));

    try {
        expect(app(PostgresClientBinaryLocator::class)->resolve('pg_dump'))
            ->toBe($this->binDir.DIRECTORY_SEPARATOR.$this->executable);
    } finally {
        if (is_string($previousPath)) {
            putenv('PATH='.$previousPath);
        }
    }
});

it('discovers a Windows PostgreSQL installation when PATH does not include the tools', function (): void {
    config()->set('gxp.backup.pgsql_bin', null);

    $path = app(PostgresClientBinaryLocator::class)->resolve('pg_dump');

    expect(is_file($path))->toBeTrue()
        ->and(basename($path))->toBe('pg_dump.exe');
})->skip(
    PHP_OS_FAMILY !== 'Windows'
        || (glob((string) getenv('ProgramFiles').'\\PostgreSQL\\*\\bin\\pg_dump.exe') ?: []) === [],
    'Requires PostgreSQL client tools under Program Files',
);
