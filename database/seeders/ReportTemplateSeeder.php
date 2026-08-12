<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\PrintLayoutRegistry;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Models\ReportTemplate;
use Illuminate\Database\Seeder;

final class ReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $registry = app(ReportFieldRegistry::class);
        $layoutRegistry = app(PrintLayoutRegistry::class);

        $templates = [
            [
                'layout_key' => 'sop-gmp-standard',
                'name' => 'GMP SOP Standard',
                'scope' => ReportScope::ControlledDocument,
                'format' => ReportFormat::Pdf,
                'description' => 'Controlled SOP layout with document identity, traceable approvals, print attribution, and organization branding.',
                'gmp_print' => true,
            ],
            [
                'layout_key' => 'structured-table-gmp-print',
                'name' => 'GMP Structured Table Print',
                'scope' => ReportScope::ControlledDocument,
                'format' => ReportFormat::Pdf,
                'description' => 'A4 controlled print layout for specifications, protocols, reports, and validation records.',
                'gmp_print' => true,
            ],
            [
                'layout_key' => 'controlled-form-gmp-print',
                'name' => 'GMP Controlled Form Print',
                'scope' => ReportScope::ControlledDocument,
                'format' => ReportFormat::Pdf,
                'description' => 'A4 controlled form layout with clear entry fields, review traceability, and controlled-copy footer.',
                'gmp_print' => true,
            ],
            [
                'layout_key' => 'batch-record-gmp-print',
                'name' => 'GMP Batch Record Print',
                'scope' => ReportScope::ControlledDocument,
                'format' => ReportFormat::Pdf,
                'description' => 'Landscape-friendly controlled layout for batch manufacturing and packaging execution records.',
                'gmp_print' => true,
            ],
            [
                'layout_key' => 'checklist-gmp-print',
                'name' => 'GMP Checklist Print',
                'scope' => ReportScope::ControlledDocument,
                'format' => ReportFormat::Pdf,
                'description' => 'Controlled checklist layout for completion, N/A justification, independent verification, and disposition.',
                'gmp_print' => true,
            ],
            [
                'layout_key' => 'repeating-log-gmp-print',
                'name' => 'GMP Repeating Log Print',
                'scope' => ReportScope::ControlledDocument,
                'format' => ReportFormat::Pdf,
                'description' => 'Controlled repeating-log layout for hourly, shift, and daily entries with supervisor review.',
                'gmp_print' => true,
            ],
            [
                'layout_key' => 'annexure-gmp-print',
                'name' => 'GMP Annexure Evidence Package Print',
                'scope' => ReportScope::ControlledDocument,
                'format' => ReportFormat::Pdf,
                'description' => 'Controlled evidence-package layout with annexure index, attachment metadata, and integrity status.',
                'gmp_print' => true,
            ],
            [
                'layout_key' => 'reports-manuals-gmp-print',
                'name' => 'GMP Reports and Manuals Print',
                'scope' => ReportScope::ControlledDocument,
                'format' => ReportFormat::Pdf,
                'description' => 'Controlled reports and manuals layout with title page, table of contents, traceable approvals, and organization branding.',
                'gmp_print' => true,
                'reports_manuals' => true,
            ],
            [
                'layout_key' => 'change-control-investigation',
                'name' => 'Change Control Investigation',
                'scope' => ReportScope::ChangeControl,
                'format' => ReportFormat::Pdf,
                'description' => 'Investigation layout with rationale, impacted documents, lifecycle milestones, and decision trail.',
            ],
            [
                'layout_key' => 'document-distribution-pdf',
                'name' => 'Document Distribution Sheet',
                'scope' => ReportScope::DocumentDistribution,
                'format' => ReportFormat::Pdf,
                'description' => 'Printable controlled-document distribution register.',
            ],
            [
                'layout_key' => 'document-distribution-csv',
                'name' => 'Document Distribution CSV',
                'scope' => ReportScope::DocumentDistribution,
                'format' => ReportFormat::Csv,
                'description' => 'UTF-8 CSV distribution register for validated downstream analysis.',
            ],
            [
                'layout_key' => 'document-distribution-excel',
                'name' => 'Document Distribution Excel',
                'scope' => ReportScope::DocumentDistribution,
                'format' => ReportFormat::Excel,
                'description' => 'Excel distribution register using the configured field order.',
            ],
            [
                'layout_key' => 'csv-validation-traceability-csv',
                'name' => 'GMP CSV Traceability Matrix',
                'scope' => ReportScope::CsvValidationTraceability,
                'format' => ReportFormat::Csv,
                'description' => 'ALCOA+ traceability from approved requirements through risk, testing, evidence, deviations, and independent review.',
            ],
            [
                'layout_key' => 'csv-validation-traceability-excel',
                'name' => 'GMP CSV Traceability Matrix',
                'scope' => ReportScope::CsvValidationTraceability,
                'format' => ReportFormat::Excel,
                'description' => 'Excel traceability matrix from approved requirements through risk, testing, evidence, deviations, and independent review.',
            ],
            [
                'layout_key' => 'csv-validation-summary-pdf',
                'name' => 'GMP CSV Validation Summary',
                'scope' => ReportScope::CsvValidationSummary,
                'format' => ReportFormat::Pdf,
                'description' => 'Printable validation summary with system identity, GxP scope, traceability totals, release baseline, ownership, and signed lifecycle history.',
            ],
        ];

        foreach ($templates as $template) {
            $scope = $template['scope'];

            $fields = ($template['gmp_print'] ?? false)
                ? $registry->defaultGmpControlledDocumentFields()
                : $registry->defaultFields($scope);

            ReportTemplate::query()->updateOrCreate(
                ['layout_key' => $template['layout_key']],
                [
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'scope' => $scope,
                    'format' => $template['format'],
                    'fields' => $fields,
                    'page_settings' => $layoutRegistry->defaultPageSettings(),
                    'header_zones' => $layoutRegistry->defaultHeaderZones(),
                    'footer_zones' => $layoutRegistry->defaultFooterZones(),
                    'toc_configuration' => ($template['reports_manuals'] ?? false)
                        ? $layoutRegistry->defaultReportsManualsTocConfiguration()
                        : $layoutRegistry->defaultTocConfiguration(),
                    'title_page_configuration' => ($template['reports_manuals'] ?? false)
                        ? $layoutRegistry->defaultReportsManualsTitlePageConfiguration()
                        : $layoutRegistry->defaultTitlePageConfiguration(),
                    'is_active' => true,
                    'is_system' => true,
                ],
            );
        }
    }
}
