<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Exceptions\SystemBackupException;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

final class SystemBackupFilePacker
{
    /**
     * @param  list<string>  $disks
     */
    public function pack(array $disks, string $absoluteZipPath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($absoluteZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new SystemBackupException('Unable to create the backup file archive.');
        }

        try {
            foreach ($disks as $disk) {
                if ($disk === 'backups') {
                    continue;
                }

                $root = rtrim(Storage::disk($disk)->path(''), DIRECTORY_SEPARATOR);

                if (! is_dir($root)) {
                    $zip->addFromString($disk.'/.keep', '');

                    continue;
                }

                $this->addDirectory($zip, $root, $disk);
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  list<string>  $disks
     */
    public function unpack(string $absoluteZipPath, array $disks): void
    {
        $zip = new ZipArchive;

        if ($zip->open($absoluteZipPath) !== true) {
            throw new SystemBackupException('Unable to open the backup file archive.');
        }

        $extractTo = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gxp-backup-files-'.bin2hex(random_bytes(8));
        mkdir($extractTo, 0755, true);

        try {
            $zip->extractTo($extractTo);
            $zip->close();

            foreach ($disks as $disk) {
                if ($disk === 'backups') {
                    continue;
                }

                $source = $extractTo.DIRECTORY_SEPARATOR.$disk;
                $destination = rtrim(Storage::disk($disk)->path(''), DIRECTORY_SEPARATOR);
                $this->replaceDirectory($source, $destination);
            }
        } finally {
            $this->deleteDirectory($extractTo);
        }
    }

    private function addDirectory(ZipArchive $zip, string $root, string $prefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        $added = false;

        foreach ($iterator as $file) {
            $absolute = $file->getPathname();
            $relative = $prefix.'/'.str_replace('\\', '/', substr($absolute, strlen($root) + 1));

            if ($file->isDir()) {
                $zip->addEmptyDir(rtrim($relative, '/'));
                $added = true;

                continue;
            }

            $zip->addFile($absolute, $relative);
            $added = true;
        }

        if (! $added) {
            $zip->addFromString($prefix.'/.keep', '');
        }
    }

    private function replaceDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $this->clearDirectory($destination);

        if (! is_dir($source)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            $target = $destination.substr($file->getPathname(), strlen($source));

            if ($file->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0755, true);
                }

                continue;
            }

            $parent = dirname($target);

            if (! is_dir($parent)) {
                mkdir($parent, 0755, true);
            }

            copy($file->getPathname(), $target);
        }
    }

    private function clearDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());

                continue;
            }

            unlink($file->getPathname());
        }
    }

    private function deleteDirectory(string $directory): void
    {
        $this->clearDirectory($directory);

        if (is_dir($directory)) {
            rmdir($directory);
        }
    }
}
