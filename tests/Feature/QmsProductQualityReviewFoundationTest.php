<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Enums\ProductQualityReviewType;
use App\Domain\QMS\Models\ProductQualityReview;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the product quality review schema', function (): void {
    expect(Schema::hasColumns('product_quality_reviews', [
        'review_number', 'type', 'status', 'title', 'product_name', 'product_code', 'dosage_form',
        'period_start_at', 'period_end_at', 'owner_id', 'created_by', 'approved_by',
        'input_summary', 'conclusions', 'recommendations', 'yield_summary', 'reject_summary',
        'started_at', 'approved_at', 'closed_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('product_quality_review_events', [
            'event_uuid', 'product_quality_review_id', 'from_status', 'to_status', 'actor_id',
            'reason', 'context', 'signature_hash', 'signature_ip_address', 'signature_user_agent',
            'occurred_at',
        ]))->toBeTrue();
});

it('persists product period ownership outputs and milestones', function (): void {
    $owner = User::factory()->create();
    $creator = User::factory()->create();
    $approver = User::factory()->create();

    $review = ProductQualityReview::factory()->create([
        'type' => ProductQualityReviewType::Interim,
        'status' => ProductQualityReviewStatus::Closed,
        'product_name' => 'Amoxicillin Capsules',
        'product_code' => 'AMX-250',
        'dosage_form' => 'Capsule',
        'period_start_at' => '2025-01-01',
        'period_end_at' => '2025-12-31',
        'owner_id' => $owner,
        'created_by' => $creator,
        'approved_by' => $approver,
        'input_summary' => 'Complaints, deviations, and changes reviewed for the product.',
        'conclusions' => 'Process capability remains acceptable.',
        'recommendations' => 'Continue annual monitoring; open CAPA for label clarity.',
        'yield_summary' => 'Mean yield 98.2%.',
        'reject_summary' => 'Reject rate 0.4%.',
        'started_at' => '2026-01-10 09:00:00',
        'approved_at' => '2026-02-01 11:00:00',
        'closed_at' => '2026-02-05 16:00:00',
    ])->refresh();

    expect($review->review_number)->toStartWith('PQR-')
        ->and($review->type)->toBe(ProductQualityReviewType::Interim)
        ->and($review->status)->toBe(ProductQualityReviewStatus::Closed)
        ->and($review->product_name)->toBe('Amoxicillin Capsules')
        ->and($review->period_start_at?->toDateString())->toBe('2025-01-01')
        ->and($review->period_end_at?->toDateString())->toBe('2025-12-31')
        ->and($review->owner?->is($owner))->toBeTrue()
        ->and($review->creator?->is($creator))->toBeTrue()
        ->and($review->approver?->is($approver))->toBeTrue()
        ->and($review->closed_at?->format('Y-m-d H:i:s'))->toBe('2026-02-05 16:00:00');
});

it('owns product quality review permissions and exposes the Filament resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:ProductQualityReview',
            'View:ProductQualityReview',
            'Create:ProductQualityReview',
            'Update:ProductQualityReview',
            'Conduct:ProductQualityReview',
            'Approve:ProductQualityReview',
            'Close:ProductQualityReview',
            'Manage:ProductQualityReview',
        )
        ->and(class_exists('App\\Filament\\Resources\\ProductQualityReviews\\ProductQualityReviewResource'))
        ->toBeTrue();
});
