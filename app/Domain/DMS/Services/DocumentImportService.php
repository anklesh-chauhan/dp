<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Jobs\FinalizeDocumentImportBatchJob;
use App\Jobs\ProcessDocumentImportItemJob;
use App\Models\DocumentImportBatch;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

final class DocumentImportService
{
    /** @var array<int, string> */
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];

    /** @param array<int, string> $paths */
    public function importFiles(array $paths, User $user, ?string $name = null): DocumentImportBatch
    {
        if ($paths === []) {
            throw new InvalidArgumentException('At least one document is required.');
        }

        $batch = DocumentImportBatch::query()->create([
            'name' => $name,
            'source_type' => count($paths) === 1 ? 'single' : 'multiple',
            'status' => 'processing',
            'created_by' => $user->getKey(),
            'started_at' => now(),
        ]);

        foreach ($paths as $path) {
            $this->importPath($batch, $path, $user);
        }

        FinalizeDocumentImportBatchJob::dispatch($batch->getKey());

        return $batch->refresh();
    }

    public function importZip(string $path, User $user, ?string $name = null): DocumentImportBatch
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            throw new InvalidArgumentException('The ZIP file could not be found.');
        }

        $zipFile = tempnam(sys_get_temp_dir(), 'qualigxp-import-');
        if ($zipFile === false) {
            throw new RuntimeException('Unable to create a temporary import file.');
        }

        try {
            file_put_contents($zipFile, $disk->get($path));
            $archive = new ZipArchive;
            if ($archive->open($zipFile) !== true) {
                throw new InvalidArgumentException('The ZIP file could not be opened.');
            }

            $batch = DocumentImportBatch::query()->create([
                'name' => $name,
                'source_type' => 'zip',
                'status' => 'processing',
                'created_by' => $user->getKey(),
                'started_at' => now(),
            ]);

            $metadataCsv = null;
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entryName = $archive->getNameIndex($index);
                if (is_string($entryName) && strtolower(pathinfo(basename($entryName), PATHINFO_EXTENSION)) === 'csv') {
                    $metadataCsv = $archive->getFromIndex($index);
                    break;
                }
            }
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entryName = $archive->getNameIndex($index);
                if (! is_string($entryName) || str_ends_with($entryName, '/')) {
                    continue;
                }

                $safeName = basename($entryName);
                if ($safeName === '' || str_contains($entryName, '..')) {
                    $this->recordFailure($batch, $safeName ?: $entryName, 'Unsafe ZIP path.');

                    continue;
                }
                if (strtolower(pathinfo($safeName, PATHINFO_EXTENSION)) === 'csv') {
                    continue;
                }

                $contents = $archive->getFromIndex($index);
                if (! is_string($contents)) {
                    $this->recordFailure($batch, $safeName, 'Unable to read ZIP entry.');

                    continue;
                }

                $storedPath = 'imports/'.Str::uuid().'/'.$safeName;
                $disk->put($storedPath, $contents);
                $this->importPath($batch, $storedPath, $user, $this->metadataFor($metadataCsv, $safeName));
            }
            $archive->close();

            FinalizeDocumentImportBatchJob::dispatch($batch->getKey());

            return $batch->refresh();
        } finally {
            @unlink($zipFile);
        }
    }

    /** @return array<string, string> */
    private function metadataFor(?string $csv, string $filename): array
    {
        if ($csv === null) {
            return [];
        }
        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            return [];
        }
        fwrite($stream, $csv);
        rewind($stream);
        $headers = fgetcsv($stream);
        $headers = is_array($headers)
            ? array_map(fn (mixed $header): string => trim(ltrim((string) $header, "\xEF\xBB\xBF")), $headers)
            : null;
        while (is_array($headers) && ($row = fgetcsv($stream)) !== false) {
            $values = array_combine($headers, array_map(fn (mixed $value): string => trim((string) $value), $row));
            if (is_array($values) && ($values['filename'] ?? null) === $filename) {
                fclose($stream);

                return array_filter($values, fn (mixed $value): bool => is_string($value) && $value !== '');
            }
        }
        fclose($stream);

        return [];
    }

    /** @param array<string, string>|null $metadata */
    private function importPath(DocumentImportBatch $batch, string $path, User $user, ?array $metadata = null): void
    {
        $disk = Storage::disk('local');
        $filename = basename($path);
        if (! in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), self::ALLOWED_EXTENSIONS, true)) {
            $this->recordFailure($batch, $filename, 'Only PDF, DOC, and DOCX files are supported.');

            return;
        }

        if (! $disk->exists($path)) {
            $this->recordFailure($batch, $filename, 'The uploaded source file could not be found.');

            return;
        }

        $stagedPath = 'import-sources/'.$batch->batch_uuid.'/'.Str::uuid().'-'.$filename;
        $disk->put($stagedPath, $disk->get($path));

        $item = $batch->items()->create([
            'original_name' => $filename,
            'source_path' => $stagedPath,
            'status' => 'pending',
            'mode' => 'archive',
            'metadata' => $metadata,
            'created_by' => $user->getKey(),
        ]);

        ProcessDocumentImportItemJob::dispatch($item->getKey());
    }

    private function recordFailure(DocumentImportBatch $batch, string $filename, string $message): void
    {
        $batch->items()->create(['original_name' => $filename, 'status' => 'failed', 'mode' => 'archive', 'error_message' => $message]);
    }
}
