<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Exceptions\SystemBackupException;

final class PostgresClientBinaryLocator
{
    public function resolve(string $name): string
    {
        $executable = PHP_OS_FAMILY === 'Windows' ? $name.'.exe' : $name;
        $configured = config('gxp.backup.pgsql_bin');

        if (is_string($configured) && $configured !== '') {
            $path = rtrim($configured, '\\/').DIRECTORY_SEPARATOR.$executable;

            if (! is_file($path)) {
                throw new SystemBackupException(
                    "PostgreSQL {$name} was not found at {$path}. Set GXP_BACKUP_PGSQL_BIN to the directory that contains pg_dump and pg_restore.",
                );
            }

            return $path;
        }

        foreach ($this->pathDirectories() as $directory) {
            $path = $directory.DIRECTORY_SEPARATOR.$executable;

            if (is_file($path)) {
                return $path;
            }
        }

        $discovered = $this->discover($executable);

        if ($discovered !== null) {
            return $discovered;
        }

        throw new SystemBackupException(
            'PostgreSQL '.$name.' was not found on PATH. Install the PostgreSQL client tools and set GXP_BACKUP_PGSQL_BIN to their bin directory (for example, C:\\Program Files\\PostgreSQL\\16\\bin).',
        );
    }

    /**
     * @return list<string>
     */
    private function pathDirectories(): array
    {
        $path = getenv('PATH');

        if (! is_string($path) || $path === '') {
            return [];
        }

        $separator = PHP_OS_FAMILY === 'Windows' ? ';' : ':';

        return array_values(array_filter(
            array_map(
                fn (string $directory): string => rtrim($directory, '\\/'),
                explode($separator, $path),
            ),
            fn (string $directory): bool => $directory !== '',
        ));
    }

    private function discover(string $executable): ?string
    {
        $matches = [];

        foreach ($this->searchDirectories() as $directory) {
            $path = rtrim($directory, '\\/').DIRECTORY_SEPARATOR.$executable;

            if (is_file($path)) {
                $matches[] = $path;
            }
        }

        if ($matches === []) {
            return null;
        }

        rsort($matches, SORT_NATURAL);

        return $matches[0];
    }

    /**
     * @return list<string>
     */
    private function searchDirectories(): array
    {
        $patterns = PHP_OS_FAMILY === 'Windows'
            ? $this->windowsSearchPatterns()
            : [
                '/usr/lib/postgresql/*/bin',
                '/usr/pgsql-*/bin',
                '/usr/bin',
                '/usr/local/bin',
                '/opt/homebrew/bin',
                '/opt/homebrew/opt/libpq/bin',
            ];

        $directories = [];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $directories = array_merge($directories, glob($pattern, GLOB_ONLYDIR) ?: []);

                continue;
            }

            if (is_dir($pattern)) {
                $directories[] = $pattern;
            }
        }

        return array_values(array_unique($directories));
    }

    /**
     * @return list<string>
     */
    private function windowsSearchPatterns(): array
    {
        $patterns = [];
        $programFiles = getenv('ProgramFiles');

        if (is_string($programFiles) && $programFiles !== '') {
            $patterns[] = $programFiles.'\\PostgreSQL\\*\\bin';
        }

        $home = getenv('USERPROFILE');

        if (is_string($home) && $home !== '') {
            $patterns[] = $home.'\\.config\\herd\\services\\*\\bin';
            $patterns[] = $home.'\\.config\\herd\\services\\*\\*\\bin';
        }

        return $patterns;
    }
}
