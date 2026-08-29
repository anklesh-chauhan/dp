<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\Deviation;
use App\Enums\ProductModule;
use App\Filament\Resources\ChangeControls\ChangeControlResource;
use App\Filament\Resources\Deviations\DeviationResource;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class SearchQualityRecords implements Tool
{
    public function __construct(
        private readonly User $user,
        private readonly ModuleManager $moduleManager,
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search deviations and change controls the authenticated user is authorized to view. Returns read-only summaries and citation URLs.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        if (! $this->moduleManager->enabled(ProductModule::QMS)) {
            return 'The QMS module is not enabled.';
        }

        $term = trim($request->string('query')->toString());
        $recordType = $request->string('record_type', 'all')->toString();
        $results = collect();

        if (in_array($recordType, ['all', 'deviation'], true)) {
            $results = $results->merge($this->deviations($term));
        }

        if (in_array($recordType, ['all', 'change_control'], true)) {
            $results = $results->merge($this->changeControls($term));
        }

        $results = $results->sortByDesc('updated_at')->take(10)->values();

        return $results->isEmpty()
            ? 'No authorized QMS records found.'
            : $results->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Reference, title, description, rationale, or immediate-action text to search for.')->required(),
            'record_type' => $schema->string()->description('One of: all, deviation, change_control.')->required(),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function deviations(string $term): Collection
    {
        if (Gate::forUser($this->user)->denies('viewAny', Deviation::class)) {
            return collect();
        }

        $pattern = '%'.$term.'%';

        return Deviation::query()
            ->where(fn (Builder $query): Builder => $query
                ->where('deviation_number', 'ilike', $pattern)
                ->orWhere('title', 'ilike', $pattern)
                ->orWhere('description', 'ilike', $pattern)
                ->orWhere('immediate_actions', 'ilike', $pattern))
            ->with(['department', 'owner'])
            ->latest('updated_at')
            ->limit(15)
            ->get()
            ->filter(fn (Deviation $deviation): bool => Gate::forUser($this->user)->allows('view', $deviation))
            ->map(fn (Deviation $deviation): array => [
                'record_type' => 'Deviation',
                'reference' => $deviation->deviation_number,
                'title' => $deviation->title,
                'status' => $deviation->status->value,
                'severity' => $deviation->severity->value,
                'department' => $deviation->department?->name,
                'owner' => $deviation->owner?->name,
                'description' => Str::limit((string) $deviation->description, 700),
                'immediate_actions' => Str::limit((string) $deviation->immediate_actions, 500),
                'updated_at' => $deviation->updated_at?->toISOString(),
                'citation_url' => DeviationResource::getUrl('view', ['record' => $deviation]),
            ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function changeControls(string $term): Collection
    {
        if (Gate::forUser($this->user)->denies('viewAny', ChangeControl::class)) {
            return collect();
        }

        $pattern = '%'.$term.'%';

        return ChangeControl::query()
            ->where(fn (Builder $query): Builder => $query
                ->where('change_number', 'ilike', $pattern)
                ->orWhere('title', 'ilike', $pattern)
                ->orWhere('description', 'ilike', $pattern)
                ->orWhere('rationale', 'ilike', $pattern))
            ->with(['department', 'owner'])
            ->latest('updated_at')
            ->limit(15)
            ->get()
            ->filter(fn (ChangeControl $changeControl): bool => Gate::forUser($this->user)->allows('view', $changeControl))
            ->map(fn (ChangeControl $changeControl): array => [
                'record_type' => 'Change Control',
                'reference' => $changeControl->change_number,
                'title' => $changeControl->title,
                'status' => $changeControl->status->value,
                'impact_classification' => $changeControl->impact_classification->value,
                'department' => $changeControl->department?->name,
                'owner' => $changeControl->owner?->name,
                'description' => Str::limit((string) $changeControl->description, 700),
                'rationale' => Str::limit((string) $changeControl->rationale, 500),
                'updated_at' => $changeControl->updated_at?->toISOString(),
                'citation_url' => ChangeControlResource::getUrl('view', ['record' => $changeControl]),
            ]);
    }
}
