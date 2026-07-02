<?php

declare(strict_types=1);

namespace App\Filament\Resources\LogDocuments\Pages;

use App\Actions\Sop\CreateDocumentFromTemplateAction;
use App\Data\SopDocumentData;
use App\Filament\Resources\LogDocuments\LogDocumentResource;
use App\Models\SopTemplateVersion;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateLogDocument extends CreateRecord
{
    protected static string $resource = LogDocumentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            $templateVersionId = $this->parseTemplateVersionId($data['template_version_id'] ?? null);

            return app(CreateDocumentFromTemplateAction::class)->execute(new SopDocumentData(
                templateId: (int) $data['template_id'],
                title: (string) $data['title'],
                ownerId: (int) $data['owner_id'],
                createdBy: Auth::id(),
                variables: $this->filterVariablesForVersion($templateVersionId, $data['variables'] ?? []),
                effectiveDate: $this->parseDate($data['effective_date'] ?? null),
                reviewDate: $this->parseDate($data['review_date'] ?? null),
                templateVersionId: $templateVersionId,
                referencedSopDocumentId: isset($data['referenced_sop_document_id']) ? (int) $data['referenced_sop_document_id'] : null,
                batchNumber: $data['batch_number'] ?? null,
                productName: $data['product_name'] ?? null,
                purpose: $data['purpose'] ?? null,
            ));
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Unable to create log document')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Please review the form and try again.')
                ->danger()
                ->send();

            throw $exception;
        }
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
