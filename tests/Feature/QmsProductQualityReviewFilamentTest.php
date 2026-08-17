<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Models\ProductQualityReview;
use App\Filament\Resources\ProductQualityReviews\Pages\ListProductQualityReviews;
use App\Filament\Resources\ProductQualityReviews\Pages\ViewProductQualityReview;
use App\Filament\Resources\ProductQualityReviews\ProductQualityReviewResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:ProductQualityReview',
        'View:ProductQualityReview',
        'Create:ProductQualityReview',
        'Update:ProductQualityReview',
        'Conduct:ProductQualityReview',
        'Approve:ProductQualityReview',
        'Close:ProductQualityReview',
        'Manage:ProductQualityReview',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces QMS entitlement for the product quality review resource', function (): void {
    expect(ProductQualityReviewResource::canAccess())->toBeTrue()
        ->and(ProductQualityReviewResource::getNavigationGroup())->toBe('QMS')
        ->and(ProductQualityReviewResource::getNavigationSort())->toBe(11);

    config()->set('modules.enabled', ['dms']);

    expect(ProductQualityReviewResource::canAccess())->toBeFalse()
        ->and(ProductQualityReviewResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(ProductQualityReviewResource::getUrl())->assertForbidden();
});

it('denies direct Livewire access without permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListProductQualityReviews::class)->assertForbidden();
});

it('delegates begin review through the transition service', function (): void {
    $review = ProductQualityReview::factory()->create(['status' => ProductQualityReviewStatus::Draft]);

    Livewire::test(ViewProductQualityReview::class, ['record' => $review->id])
        ->callAction('begin', ['reason' => 'Annual PQR started for the product.'])
        ->assertNotified();

    expect($review->fresh()?->status)->toBe(ProductQualityReviewStatus::InProgress)
        ->and($review->auditEvents()->count())->toBe(1);
});
