<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ProductReturnDisposition;
use App\Domain\QMS\Enums\ProductReturnStatus;
use App\Domain\QMS\Models\ProductReturn;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the product return schema', function (): void {
    expect(Schema::hasColumns('product_returns', [
        'return_number',
        'status',
        'disposition',
        'product_name',
        'batch_number',
        'quantity',
        'unit',
        'reason',
        'source',
        'product_recall_id',
        'owner_id',
        'created_by',
        'received_at',
        'quarantined_at',
        'dispositioned_at',
        'closed_at',
        'qa_disposition_notes',
    ]))->toBeTrue()
        ->and(Schema::hasTable('product_return_events'))->toBeTrue();
});

it('persists product return fields', function (): void {
    $owner = User::factory()->create();
    $creator = User::factory()->create();

    $return = ProductReturn::factory()->create([
        'status' => ProductReturnStatus::Draft,
        'disposition' => ProductReturnDisposition::Pending,
        'product_name' => 'Example Capsule',
        'batch_number' => 'LOT-99',
        'quantity' => 12.5,
        'unit' => 'bottles',
        'reason' => 'Customer return after temperature excursion.',
        'source' => 'distributor',
        'owner_id' => $owner,
        'created_by' => $creator,
    ])->refresh();

    expect($return->return_number)->toStartWith('PRT-')
        ->and($return->status)->toBe(ProductReturnStatus::Draft)
        ->and($return->disposition)->toBe(ProductReturnDisposition::Pending)
        ->and((float) $return->quantity)->toBe(12.5)
        ->and($return->owner?->is($owner))->toBeTrue()
        ->and($return->creator?->is($creator))->toBeTrue();
});

it('owns product return permissions and exposes the Filament resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:ProductReturn',
            'View:ProductReturn',
            'Create:ProductReturn',
            'Update:ProductReturn',
            'Receive:ProductReturn',
            'Quarantine:ProductReturn',
            'Dispose:ProductReturn',
            'Close:ProductReturn',
            'Manage:ProductReturn',
        )
        ->and(class_exists('App\\Filament\\Resources\\ProductReturns\\ProductReturnResource'))
        ->toBeTrue();
});
