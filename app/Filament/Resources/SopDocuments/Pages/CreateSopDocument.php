<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\Pages;

use App\Actions\Sop\CreateDocumentFromTemplateAction;
use App\Data\SopDocumentData;
use App\Filament\Resources\SopDocuments\SopDocumentResource;
use App\Models\SopTemplateVersion;
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
        $templateVersionId = $this->parseTemplateVersionId($data['template_version_id'] ?? null);

        return app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
            (int) $data['template_id'],
            (string) $data['title'],
            (int) $data['owner_id'],
            Auth::id(),
            $this->filterVariablesForVersion($templateVersionId, $data['variables'] ?? []),
            $this->parseDate($data['effective_date'] ?? null),
            $this->parseDate($data['review_date'] ?? null),
            $templateVersionId,
            $data['document_number'] ?? null,
        ));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    private function filterVariablesForVersion(?int $templateVersionId, array $variables): array
    {
        if ($templateVersionId === null) {
            return $variables;
        }

        $allowedNames = SopTemplateVersion::query()
            ->with('variables')
            ->find($templateVersionId)
            ?->variables
            ->pluck('name')
            ->all() ?? [];

        return collect($variables)
            ->only($allowedNames)
            ->all();
    }

    private function parseTemplateVersionId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }

        return (int) $value;
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
