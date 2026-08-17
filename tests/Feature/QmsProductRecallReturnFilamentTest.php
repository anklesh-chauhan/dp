<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Enums\ProductRecallType;
use App\Domain\QMS\Enums\ProductReturnStatus;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\ProductRecall;
use App\Domain\QMS\Models\ProductReturn;
use App\Filament\Resources\Complaints\Pages\ViewComplaint;
use App\Filament\Resources\ProductRecalls\Pages\ListProductRecalls;
use App\Filament\Resources\ProductRecalls\Pages\ViewProductRecall;
use App\Filament\Resources\ProductRecalls\ProductRecallResource;
use App\Filament\Resources\ProductReturns\Pages\ListProductReturns;
use App\Filament\Resources\ProductReturns\Pages\ViewProductReturn;
use App\Filament\Resources\ProductReturns\ProductReturnResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:ProductRecall',
        'View:ProductRecall',
        'Create:ProductRecall',
        'Update:ProductRecall',
        'Initiate:ProductRecall',
        'ViewAny:ProductReturn',
        'View:ProductReturn',
        'Create:ProductReturn',
        'Update:ProductReturn',
        'Receive:ProductReturn',
        'View:Complaint',
        'ViewAny:Complaint',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces QMS entitlement for product recall and return resources', function (): void {
    expect(ProductRecallResource::canAccess())->toBeTrue()
        ->and(ProductReturnResource::canAccess())->toBeTrue()
        ->and(ProductRecallResource::getNavigationSort())->toBe(12)
        ->and(ProductReturnResource::getNavigationSort())->toBe(13);

    config()->set('modules.enabled', ['dms']);

    expect(ProductRecallResource::canAccess())->toBeFalse()
        ->and(ProductReturnResource::canAccess())->toBeFalse()
        ->and(ProductRecallResource::shouldRegisterNavigation())->toBeFalse()
        ->and(ProductReturnResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(ProductRecallResource::getUrl())->assertForbidden();
    $this->get(ProductReturnResource::getUrl())->assertForbidden();
});

it('denies direct Livewire access without permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListProductRecalls::class)->assertForbidden();
    Livewire::test(ListProductReturns::class)->assertForbidden();
});

it('delegates product recall and return lifecycle actions through transition services', function (): void {
    $recall = ProductRecall::factory()->create(['status' => ProductRecallStatus::Draft]);
    Livewire::test(ViewProductRecall::class, ['record' => $recall->id])
        ->callAction('initiate', ['reason' => 'Market recall initiated from quality signal.'])
        ->assertNotified();
    expect($recall->fresh()?->status)->toBe(ProductRecallStatus::Initiated)
        ->and($recall->auditEvents()->count())->toBe(1);

    $return = ProductReturn::factory()->create(['status' => ProductReturnStatus::Draft]);
    Livewire::test(ViewProductReturn::class, ['record' => $return->id])
        ->callAction('receive', ['reason' => 'Returned goods logged into warehouse.'])
        ->assertNotified();
    expect($return->fresh()?->status)->toBe(ProductReturnStatus::Received)
        ->and($return->auditEvents()->count())->toBe(1);
});

it('opens a product recall from a complaint through the Filament handoff action', function (): void {
    $complaint = Complaint::factory()->create([
        'status' => ComplaintStatus::UnderAssessment,
        'product_name' => 'Example Syrup',
        'batch_number' => 'LOT-55',
    ]);

    Livewire::test(ViewComplaint::class, ['record' => $complaint->id])
        ->callAction('openProductRecall', [
            'type' => ProductRecallType::Market->value,
            'reason' => 'Complaint indicates distributed defective packs.',
        ])
        ->assertNotified();

    $recall = ProductRecall::query()->sole();

    expect($recall->complaint_id)->toBe($complaint->id)
        ->and($recall->product_name)->toBe('Example Syrup')
        ->and($recall->batch_numbers)->toBe(['LOT-55'])
        ->and($recall->status)->toBe(ProductRecallStatus::Draft);
});
