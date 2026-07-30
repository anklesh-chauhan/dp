<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Services;

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Models\ReportTemplate;
use Illuminate\Database\Eloquent\Model;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class TabularReportExporter
{
    public function __construct(private ReportFieldRegistry $fieldRegistry) {}

    /** @param iterable<Model> $records */
    public function download(ReportTemplate $template, iterable $records, string $filename): StreamedResponse
    {
        abort_unless(in_array($template->format, [ReportFormat::Csv, ReportFormat::Excel], true), 422);

        $fields = collect($template->fields)
            ->filter(fn (array $field): bool => (bool) ($field['enabled'] ?? false))
            ->values();

        abort_if($fields->isEmpty(), 422, 'At least one report field must be enabled.');

        return $template->format === ReportFormat::Csv
            ? $this->csv($fields->all(), $records, "{$filename}.csv")
            : $this->excel($fields->all(), $records, "{$filename}.xlsx");
    }

    /**
     * @param  list<array{key: string, label: string}>  $fields
     * @param  iterable<Model>  $records
     */
    private function csv(array $fields, iterable $records, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($fields, $records): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, array_column($fields, 'label'));

            foreach ($records as $record) {
                fputcsv($stream, array_map(
                    fn (array $field): string|int => $this->fieldRegistry->value($record, $field['key']),
                    $fields,
                ));
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  list<array{key: string, label: string}>  $fields
     * @param  iterable<Model>  $records
     */
    private function excel(array $fields, iterable $records, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($fields, $records, $filename): void {
            $writer = new Writer;
            $writer->openToBrowser($filename);
            $writer->addRow(Row::fromValues(array_column($fields, 'label')));

            foreach ($records as $record) {
                $writer->addRow(Row::fromValues(array_map(
                    fn (array $field): string|int => $this->fieldRegistry->value($record, $field['key']),
                    $fields,
                )));
            }

            $writer->close();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
