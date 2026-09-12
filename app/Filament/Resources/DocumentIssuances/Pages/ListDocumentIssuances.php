<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentIssuances\Pages;

use App\Filament\Resources\DocumentIssuances\DocumentIssuanceResource;
use App\Models\DocumentIssuance;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDocumentIssuances extends ListRecords
{
    protected static string $resource = DocumentIssuanceResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All copies')
                ->badge(fn (): int => DocumentIssuanceResource::getEloquentQuery()->count())
                ->deferBadge(),
            'reference' => Tab::make('Reference copy')
                ->badge(fn (): int => $this->copyTypeCount(DocumentIssuance::TYPE_REFERENCE))
                ->deferBadge()
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('issuance_type', DocumentIssuance::TYPE_REFERENCE)),
            'execution' => Tab::make('Writable execution record')
                ->badge(fn (): int => $this->copyTypeCount(DocumentIssuance::TYPE_EXECUTION))
                ->deferBadge()
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('issuance_type', DocumentIssuance::TYPE_EXECUTION)),
            'paper' => Tab::make('Paper copy')
                ->badge(fn (): int => $this->copyTypeCount(DocumentIssuance::TYPE_PAPER))
                ->deferBadge()
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('issuance_type', DocumentIssuance::TYPE_PAPER)),
        ];
    }

    private function copyTypeCount(string $type): int
    {
        return DocumentIssuanceResource::getEloquentQuery()
            ->where('issuance_type', $type)
            ->count();
    }
}
