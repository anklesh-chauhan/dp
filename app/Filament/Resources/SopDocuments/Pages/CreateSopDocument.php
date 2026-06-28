<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\Pages;

use App\Actions\Sop\CreateDocumentFromTemplateAction;
use App\Data\SopDocumentData;
use App\Filament\Resources\SopDocuments\SopDocumentResource;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateSopDocument extends CreateRecord
{
    protected static string $resource = SopDocumentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
            (int) $data['template_id'],
            (string) $data['title'],
            (int) $data['owner_id'],
            Auth::id(),
            $data['variables'] ?? [],
            $this->parseDate($data['effective_date'] ?? null),
            $this->parseDate($data['review_date'] ?? null),
            isset($data['template_version_id']) ? (int) $data['template_version_id'] : null,
            $data['document_number'] ?? null,
        ));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    private function parseDate(mixed $date): ?CarbonInterface
    {
        if ($date === null || $date === '') {
            return null;
        }

        if ($date instanceof CarbonInterface) {
            return $date;
        }

        return CarbonImmutable::parse($date);
    }
}
