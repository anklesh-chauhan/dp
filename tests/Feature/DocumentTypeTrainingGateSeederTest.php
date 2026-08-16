<?php

declare(strict_types=1);

use App\Models\DocumentType;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds training-before-effective only for SOP, Policy, and Manual', function (): void {
    $this->seed(LookupTableSeeder::class);

    $gated = DocumentType::query()
        ->where('requires_training_before_effective', true)
        ->orderBy('code')
        ->pluck('code')
        ->all();

    expect($gated)->toEqualCanonicalizing(DocumentType::defaultCodesRequiringTrainingBeforeEffective())
        ->and(DocumentType::query()->where('code', DocumentType::FORM)->value('requires_training_before_effective'))->toBeFalse()
        ->and(DocumentType::query()->where('code', DocumentType::LOG)->value('requires_training_before_effective'))->toBeFalse()
        ->and(DocumentType::query()->where('code', 'REPORT')->value('requires_training_before_effective'))->toBeFalse();
});
