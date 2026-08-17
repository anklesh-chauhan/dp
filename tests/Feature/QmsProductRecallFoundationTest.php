<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ProductRecallClassification;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Enums\ProductRecallType;
use App\Domain\QMS\Models\ProductRecall;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the product recall schema', function (): void {
    expect(Schema::hasColumns('product_recalls', [
        'recall_number',
        'type',
        'status',
        'classification',
        'title',
        'description',
        'product_name',
        'product_code',
        'batch_numbers',
        'market_countries',
        'complaint_id',
        'owner_id',
        'created_by',
        'initiated_at',
        'classified_at',
        'notified_at',
        'executed_at',
        'effectiveness_verified_at',
        'closed_at',
        'effectiveness_summary',
        'is_mock',
    ]))->toBeTrue()
        ->and(Schema::hasTable('product_recall_events'))->toBeTrue();
});

it('persists product recall fields and marks mock recalls', function (): void {
    $owner = User::factory()->create();
    $creator = User::factory()->create();

    $recall = ProductRecall::factory()->create([
        'type' => ProductRecallType::Mock,
        'status' => ProductRecallStatus::Draft,
        'classification' => ProductRecallClassification::NotClassified,
        'title' => 'Mock recall exercise',
        'product_name' => 'Example Tablet',
        'product_code' => 'SKU-100',
        'batch_numbers' => ['LOT-1', 'LOT-2'],
        'market_countries' => 'IN, US',
        'owner_id' => $owner,
        'created_by' => $creator,
    ])->refresh();

    expect($recall->recall_number)->toStartWith('RCL-')
        ->and($recall->type)->toBe(ProductRecallType::Mock)
        ->and($recall->is_mock)->toBeTrue()
        ->and($recall->batch_numbers)->toBe(['LOT-1', 'LOT-2'])
        ->and($recall->owner?->is($owner))->toBeTrue()
        ->and($recall->creator?->is($creator))->toBeTrue();
});

it('owns product recall permissions and exposes the Filament resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:ProductRecall',
            'View:ProductRecall',
            'Create:ProductRecall',
            'Update:ProductRecall',
            'Initiate:ProductRecall',
            'Classify:ProductRecall',
            'Notify:ProductRecall',
            'Execute:ProductRecall',
            'Verify:ProductRecall',
            'Close:ProductRecall',
            'Manage:ProductRecall',
        )
        ->and(class_exists('App\\Filament\\Resources\\ProductRecalls\\ProductRecallResource'))
        ->toBeTrue();
});
