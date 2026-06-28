<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\Pages;

use App\Filament\Resources\SopDocuments\SopDocumentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSopDocument extends EditRecord
{
    protected static string $resource = SopDocumentResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('printPdf')
                ->label('Print / PDF')
                ->icon(Heroicon::Printer)
                ->url(fn (): string => route('sop-documents.print', $this->record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
