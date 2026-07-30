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
            ['sop-gmp-standard', 'GMP SOP Header / Footer', ReportScope::ControlledDocument, ReportFormat::Pdf, 'Controlled SOP layout with document identity, traceable approvals, print attribution, and organization branding.'],
            ['change-control-investigation', 'Change Control Investigation', ReportScope::ChangeControl, ReportFormat::Pdf, 'Investigation layout with rationale, impacted documents, lifecycle milestones, and decision trail.'],
            ['document-distribution-pdf', 'Document Distribution Sheet', ReportScope::DocumentDistribution, ReportFormat::Pdf, 'Printable controlled-document distribution register.'],
            ['document-distribution-csv', 'Document Distribution CSV', ReportScope::DocumentDistribution, ReportFormat::Csv, 'UTF-8 CSV distribution register for validated downstream analysis.'],
            ['document-distribution-excel', 'Document Distribution Excel', ReportScope::DocumentDistribution, ReportFormat::Excel, 'Excel distribution register using the configured field order.'],
        ];

        foreach ($templates as [$layoutKey, $name, $scope, $format, $description]) {
            $fields = $registry->defaultFields($scope);

            if ($scope === ReportScope::ControlledDocument) {
                $fields = collect($fields)
                    ->map(function (array $field): array {
                        if (in_array($field['key'], ['organization', 'document_identity', 'status', 'audit_reference', 'footer'], true)) {
                            $field['enabled'] = false;
                        }

                        return $field;
                    })
                    ->all();
            }

            ReportTemplate::query()->updateOrCreate(
                ['layout_key' => $layoutKey],
                [
                    'name' => $name,
                    'description' => $description,
                    'scope' => $scope,
                    'format' => $format,
                    'fields' => $fields,
                    'page_settings' => $layoutRegistry->defaultPageSettings(),
                    'header_zones' => $layoutRegistry->defaultHeaderZones(),
                    'footer_zones' => $layoutRegistry->defaultFooterZones(),
                    'is_active' => true,
                    'is_system' => true,
                ],
            );
        }
    }
}
