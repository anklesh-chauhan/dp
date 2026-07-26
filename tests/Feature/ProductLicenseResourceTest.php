<?php

declare(strict_types=1);

use App\Enums\ProductLicenseAuditEventType;
use App\Enums\ProductLicenseState;
use App\Filament\Resources\ProductLicenses\Pages\ListProductLicenses;
use App\Filament\Resources\ProductLicenses\Pages\ViewProductLicense;
use App\Filament\Resources\ProductLicenses\ProductLicenseResource;
use App\Filament\Resources\ProductLicenses\RelationManagers\AuditEventsRelationManager;
use App\Models\ProductLicense;
use App\Models\ProductLicenseAuditEvent;
use App\Models\User;
use App\Support\Modules\Contracts\ProductLicenseStateResolver;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::findOrCreate('ViewAny:ProductLicense', 'web');
    Permission::findOrCreate('View:ProductLicense', 'web');

    $this->actingAs(
        User::factory()->create()->givePermissionTo([
            'ViewAny:ProductLicense',
            'View:ProductLicense',
        ]),
    );

    app()->instance(
        ProductLicenseStateResolver::class,
        new class implements ProductLicenseStateResolver
        {
            public function resolve(ProductLicense $license, ?DateTimeInterface $at = null): ProductLicenseState
            {
                return ProductLicenseState::Active;
            }
        },
    );
});

it('registers only index and view pages', function (): void {
    expect(ProductLicenseResource::getPages())
        ->toHaveKeys(['index', 'view'])
        ->not->toHaveKeys(['create', 'edit']);
});

it('does not expose license mutation operations', function (): void {
    $license = ProductLicense::factory()->create();

    expect(ProductLicenseResource::canCreate())->toBeFalse()
        ->and(ProductLicenseResource::canEdit($license))->toBeFalse()
        ->and(ProductLicenseResource::canDelete($license))->toBeFalse()
        ->and(ProductLicenseResource::canDeleteAny())->toBeFalse();
});

it('requires core license visibility permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListProductLicenses::class)
        ->assertForbidden();
});

it('shows lifecycle and module metadata without exposing signed material', function (): void {
    $license = ProductLicense::factory()->create([
        'license_key' => '6f4ef820-4574-4f26-a71f-75a5203cba1b',
        'key_id' => 'issuer-production-2026',
        'payload' => json_encode([
            'license_key' => '6f4ef820-4574-4f26-a71f-75a5203cba1b',
            'modules' => ['dms', 'qms'],
            'private_claim' => 'must-not-render',
        ], JSON_THROW_ON_ERROR),
        'signature' => 'secret-detached-signature',
    ]);

    Livewire::test(ViewProductLicense::class, ['record' => $license->getKey()])
        ->assertSuccessful()
        ->assertSee('6f4ef820-4574-4f26-a71f-75a5203cba1b')
        ->assertSee('issuer-production-2026')
        ->assertSee('dms')
        ->assertSee('qms')
        ->assertDontSee('must-not-render')
        ->assertDontSee('secret-detached-signature');

    expect($license->auditEvents()->count())->toBe(0);
});

it('renders append-only audit history without mutation actions', function (): void {
    $license = ProductLicense::factory()->create();
    $events = ProductLicenseAuditEvent::factory()
        ->count(2)
        ->sequence(
            [
                'product_license_id' => $license->getKey(),
                'event_type' => ProductLicenseAuditEventType::Activated,
                'from_state' => null,
                'to_state' => ProductLicenseState::Active,
            ],
            [
                'product_license_id' => $license->getKey(),
                'event_type' => ProductLicenseAuditEventType::GraceStarted,
                'from_state' => ProductLicenseState::Active,
                'to_state' => ProductLicenseState::Grace,
            ],
        )
        ->create();

    Livewire::test(AuditEventsRelationManager::class, [
        'ownerRecord' => $license,
        'pageClass' => ViewProductLicense::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($events)
        ->assertActionDoesNotExist(TestAction::make('create')->table())
        ->assertActionDoesNotExist(TestAction::make('edit')->table())
        ->assertActionDoesNotExist(TestAction::make('delete')->table());
});
