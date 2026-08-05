<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Models\ControlledDocument;
use Smalot\PdfParser\Parser;

final class DocumentTocPageResolver
{
    /** @return array<int, int> */
    public function resolve(string $pdfContents, ControlledDocument $document): array
    {
        $pdf = (new Parser)->parseContent($pdfContents);
        $pageNumbers = [];

        foreach ($pdf->getPages() as $pageIndex => $page) {
            $text = $page->getText();

            foreach ($document->sections as $section) {
                $marker = $this->marker($section->getKey());

                if (str_contains($text, $marker)) {
                    $pageNumbers[$section->getKey()] = $pageIndex + 1;
                }
            }
        }

        return $pageNumbers;
    }

    public function marker(int|string $sectionId): string
    {
        return "TOC_SECTION_{$sectionId}";
    }
}
