<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\ContentIntegrityHasher;
use Illuminate\Support\Facades\Storage;

final class Sha256ContentIntegrityHasher implements ContentIntegrityHasher
{
    public function hash(string $disk, string $path): ?string
    {
        $filesystem = Storage::disk($disk);

        if ($filesystem->missing($path)) {
            return null;
        }

        $stream = $filesystem->readStream($path);

        if (! is_resource($stream)) {
            return null;
        }

        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);

            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
