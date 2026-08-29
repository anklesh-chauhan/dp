<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class SearchControlledDocuments implements Tool
{
    public function __construct(private readonly User $user) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search approved or effective controlled documents the authenticated user is authorized to view. Returns source excerpts and citation URLs.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        if (Gate::forUser($this->user)->denies('viewAny', ControlledDocument::class)) {
            return 'No authorized controlled-document results found.';
        }

        $term = trim($request->string('query')->toString());

        if ($term === '') {
            return 'No authorized controlled-document results found.';
        }

        $pattern = '%'.$term.'%';
        $documents = ControlledDocument::query()
            ->whereHas('documentStatus', fn (Builder $query): Builder => $query->whereIn('code', [
                DocumentStatus::APPROVED,
                DocumentStatus::EFFECTIVE,
            ]))
            ->where(function (Builder $query) use ($pattern): void {
                $query->where('document_number', 'ilike', $pattern)
                    ->orWhere('title', 'ilike', $pattern)
                    ->orWhere('purpose', 'ilike', $pattern)
                    ->orWhereHas('sections', fn (Builder $sectionQuery): Builder => $sectionQuery
                        ->where('title', 'ilike', $pattern)
                        ->orWhere('content', 'ilike', $pattern));
            })
            ->with(['documentStatus', 'department', 'documentType', 'sections'])
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->filter(fn (ControlledDocument $document): bool => Gate::forUser($this->user)->allows('view', $document))
            ->take(8)
            ->map(fn (ControlledDocument $document): array => [
                'reference' => $document->document_number,
                'title' => $document->title,
                'version' => $document->version,
                'status' => $document->documentStatus?->name,
                'department' => $document->department?->name,
                'document_type' => $document->documentType?->name,
                'effective_date' => $document->effective_date?->toDateString(),
                'purpose' => Str::limit(strip_tags((string) $document->purpose), 500),
                'source_excerpts' => $document->sections
                    ->take(4)
                    ->map(fn ($section): array => [
                        'section' => $section->title,
                        'content' => Str::limit(strip_tags((string) $section->content), 700),
                    ])
                    ->values()
                    ->all(),
                'citation_url' => ControlledDocumentResource::getUrl('view', ['record' => $document]),
            ])
            ->values();

        return $documents->isEmpty()
            ? 'No authorized controlled-document results found.'
            : $documents->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Document number, title, topic, purpose, or section text to search for.')->required(),
        ];
    }
}
