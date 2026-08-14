<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class OperationalReportCsvExporter
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<string|int|float|null>>  $rows
     */
    public function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $downloadName = str_ends_with($filename, '.csv') ? $filename : "{$filename}.csv";

        return response()->streamDownload(function () use ($headers, $rows): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers);

            foreach ($rows as $row) {
                fputcsv($stream, array_map(
                    fn (string|int|float|null $value): string => $value === null ? '' : (string) $value,
                    $row,
                ));
            }

            fclose($stream);
        }, $downloadName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
