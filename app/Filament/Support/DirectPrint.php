<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\DocumentIssuanceBatch;
use Livewire\Component;

class DirectPrint
{
    public static function open(Component $livewire, string $url): void
    {
        $livewire->js('window.open('.json_encode($url).', "_blank", "noopener")');
    }

    public static function documentUrl(ControlledDocument $document): string
    {
        return route('controlled-documents.viewer', [
            'controlledDocument' => $document,
            'print' => 1,
        ]);
    }

    public static function copyUrl(DocumentIssuance $issuance): string
    {
        return route('controlled-documents.viewer', [
            'controlledDocument' => $issuance->document_id,
            'issuance' => $issuance->id,
            'print' => 1,
        ]);
    }

    public static function batchUrl(DocumentIssuanceBatch $batch): string
    {
        return route('issuance-batches.print', $batch);
    }
}
