<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\Pages;

use App\Actions\Sop\CreateDocumentFromTemplateAction;
use App\Data\SopDocumentData;
use App\Filament\Resources\SopDocuments\SopDocumentResource;
use App\Models\SopTemplateVersion;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateSopDocument extends CreateRecord
{
    protected static string $resource = SopDocumentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            $templateVersionId = $this->parseTemplateVersionId($data['template_version_id'] ?? null);

            return app(CreateDocumentFromTemplateAction::class)->execute(
                new SopDocumentData(
                    (int) $data['template_id'],
                    (string) $data['title'],
                    (int) $data['owner_id'],
                    Auth::id(),
                    $this->filterVariablesForVersion($templateVersionId, $data['variables'] ?? []),
                    $this->parseDate($data['effective_date'] ?? null),
                    $this->parseDate($data['review_date'] ?? null),
                    $templateVersionId,
                    $data['document_number'] ?? null,
                    $data['referenced_sop_document_id'] ?? null,
                    $data['referenced_sop_number'] ?? null,
                    $data['referenced_sop_version'] ?? null,
                    $data['referenced_sop_effective_date'] ?? null,
                    $data['batch_number'] ?? null,
                    $data['product_name'] ?? null,
                    $data['purpose'] ?? null,
                    $data['document_status_id'] ?? null,
                    $data['effective_date'] ?? null,
                    $data['review_date'] ?? null,
                    $data['owner_id'] ?? null,
                    $data['created_by'] ?? null,
                    $data['locked_by'] ?? null,
                    $data['locked_at'] ?? null,
                )
            );
        } catch (ValidationException $e) {

            Notification::make()
                ->title('Unable to create document')
                ->body(collect($e->errors())->flatten()->implode("\n"))
                ->danger()
                ->persistent()
                ->send();

            throw $e;
        } catch (\Throwable $e) {

            Notification::make()
                ->title('Unable to create document')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            throw $e;
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
