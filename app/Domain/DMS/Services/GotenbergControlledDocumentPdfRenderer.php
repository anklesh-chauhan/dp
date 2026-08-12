<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\DMS\Contracts\ControlledDocumentPdfRenderer;
use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\ReportTemplate;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

class GotenbergControlledDocumentPdfRenderer implements ControlledDocumentPdfRenderer
{
    private const float HEADER_TEMPLATE_INSET_MM = 5;

    public function render(
        ControlledDocument $document,
        ReportTemplate $reportTemplate,
        ?DocumentIssuance $issuance,
        array $organization,
    ): string {
        $organization = $this->embedOrganizationLogo($organization);
        $pageSettings = $reportTemplate->printPageSettings();
        $headerZones = $reportTemplate->printHeaderZones();
        $footerZones = $reportTemplate->printFooterZones();
        $titlePage = $reportTemplate->titlePageConfiguration();
        $headerZones = $titlePage['enabled'] && ! $titlePage['show_header'] ? [...$headerZones, 'repeat_every_page' => false] : $headerZones;
        $footerZones = $titlePage['enabled'] && ! $titlePage['show_footer'] ? [...$footerZones, 'repeat_every_page' => false] : $footerZones;
        $topMargin = (float) $pageSettings['margin_top_mm'] + ($headerZones['repeat_every_page'] ? self::HEADER_TEMPLATE_INSET_MM + $this->estimatedHeaderHeight($headerZones, (float) $pageSettings['font_size']) + (float) $headerZones['content_gap_mm'] : 0);
        $bottomMargin = (float) $pageSettings['margin_bottom_mm'] + ($footerZones['repeat_every_page'] ? $this->estimatedFooterHeight($footerZones, (float) $pageSettings['font_size']) + (float) $footerZones['content_gap_mm'] : 0);
        $data = [
            'document' => $document,
            'issuance' => $issuance,
            'reportTemplate' => $reportTemplate,
            'enabledFields' => $reportTemplate->enabledFieldKeys(),
            'organization' => $organization,
            'serverPdf' => true,
            'serverPdfMargins' => [
                'top' => $topMargin,
                'right' => (float) $pageSettings['margin_right_mm'],
                'bottom' => $bottomMargin,
                'left' => (float) $pageSettings['margin_left_mm'],
            ],
        ];

        $toc = $reportTemplate->tocConfiguration();

        if ($toc['enabled'] && $document->sections->contains(fn ($section): bool => $section->include_in_toc)) {
            $markerData = [...$data, 'tocMarkerMode' => true, 'tocPageNumbers' => []];
            $markerPdf = $this->generate($markerData, $pageSettings, $headerZones, $footerZones);
            $resolver = app(DocumentTocPageResolver::class);
            $pageNumbers = $resolver->resolve($markerPdf, $document);

            for ($attempt = 0; $attempt < 3; $attempt++) {
                $data['tocMarkerMode'] = true;
                $data['tocPageNumbers'] = $pageNumbers;
                $finalPdf = $this->generate($data, $pageSettings, $headerZones, $footerZones);
                $verifiedPageNumbers = $resolver->resolve($finalPdf, $document);

                if ($verifiedPageNumbers === $pageNumbers) {
                    return $finalPdf;
                }

                $pageNumbers = $verifiedPageNumbers;
            }

            return $finalPdf;
        }

        return $this->generate($data, $pageSettings, $headerZones, $footerZones);
    }

    /** @param array<string, mixed> $data */
    private function generate(array $data, array $pageSettings, array $headerZones, array $footerZones): string
    {

        $builder = Pdf::view('controlled-documents.print', $data)
            ->driver('gotenberg')
            ->format($pageSettings['paper_size'])
            ->orientation($pageSettings['orientation'])
            ->margins(
                top: (float) $data['serverPdfMargins']['top'],
                right: (float) $pageSettings['margin_right_mm'],
                bottom: (float) $data['serverPdfMargins']['bottom'],
                left: (float) $pageSettings['margin_left_mm'],
            );

        if ($headerZones['repeat_every_page']) {
            $builder->headerView('controlled-documents.pdf-header', $data);
        }

        if ($footerZones['repeat_every_page']) {
            $builder->footerView('controlled-documents.pdf-footer', $data);
        }

        return $builder->generatePdfContent();
    }

    /** @param array<string, mixed> $headerZones */
    private function estimatedHeaderHeight(array $headerZones, float $fontSize): float
    {
        $lineHeight = $fontSize * 0.352778 * 1.2;

        return collect($headerZones['rows'])->sum(function (array $row) use ($lineHeight): float {
            $largestCell = collect($row['cells'])
                ->max(fn (array $cell): int => count($cell['items']));

            return max(7, ((int) $largestCell * $lineHeight) + 3);
        });
    }

    /** @param array<string, mixed> $footerZones */
    private function estimatedFooterHeight(array $footerZones, float $fontSize): float
    {
        $lineHeight = $fontSize * 0.352778 * 1.2;

        return collect($footerZones['rows'])->sum(function (array $row) use ($lineHeight): float {
            $largestCell = collect($row['cells'])
                ->max(fn (array $cell): int => count($cell['items']));

            return max(7, ((int) $largestCell * $lineHeight) + 3);
        });
    }

    /**
     * @param  array<string, mixed>  $organization
     * @return array<string, mixed>
     */
    private function embedOrganizationLogo(array $organization): array
    {
        $logoPath = $organization['logo_path'] ?? null;

        if (! is_string($logoPath) || $logoPath === '' || ! Storage::disk('public')->exists($logoPath)) {
            return $organization;
        }

        $mimeType = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
        $organization['logo_data_uri'] = 'data:'.$mimeType.';base64,'.base64_encode(
            Storage::disk('public')->get($logoPath),
        );

        return $organization;
    }
}
