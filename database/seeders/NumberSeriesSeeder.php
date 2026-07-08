<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\NumberSeries;
use App\Models\NumberSeriesSetting;
use Illuminate\Database\Seeder;

class NumberSeriesSeeder extends Seeder
{
    public function run(): void
    {
        NumberSeriesSetting::current();

        DocumentType::query()
            ->orderBy('code')
            ->each(function (DocumentType $documentType): void {
                NumberSeries::query()->firstOrCreate([
                    'document_type_id' => $documentType->id,
                ]);
            });
    }
}
