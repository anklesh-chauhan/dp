<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\ProductQualityReview;
use App\Domain\QMS\Models\ProductQualityReviewEvent;
use App\Domain\QMS\Services\ProductQualityReviewTransitionService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    $this->permissions = [
        'Conduct:ProductQualityReview',
        'Approve:ProductQualityReview',
        'Close:ProductQualityReview',
        'Manage:ProductQualityReview',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->owner = User::factory()->create();
    $this->creator = User::factory()->create();
    $this->review = ProductQualityReview::factory()->create([
        'product_name' => 'Test Product Alpha',
        'owner_id' => $this->owner,
        'created_by' => $this->creator,
    ]);
});

it('records begin review approval and closure with signed consequential history', function (): void {
    $service = app(ProductQualityReviewTransitionService::class);
    $service->transition($this->review, ProductQualityReviewStatus::InProgress, $this->actor, 'Annual PQR started.');
    $underReview = $service->transition(
        $this->review,
        ProductQualityReviewStatus::UnderReview,
        $this->actor,
        'Inputs compiled for QA review.',
        inputSummary: 'Deviations, complaints, and change controls for the period were reviewed.',
    );
    $approved = $service->transition(
        $underReview,
        ProductQualityReviewStatus::Approved,
        $this->actor,
        'Independent QA approval of PQR conclusions.',
        conclusions: 'No adverse quality trends requiring process redesign.',
        recommendations: 'Maintain current controls; monitor complaint trend next period.',
        ipAddress: '203.0.113.55',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );
    $closed = $service->transition(
        $approved,
        ProductQualityReviewStatus::Closed,
        $this->actor,
        'PQR package archived.',
        ipAddress: '203.0.113.55',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );

    $events = $closed->auditEvents()->orderBy('id')->get();
    $approvalEvent = $events->get(2);

    expect($closed->status)->toBe(ProductQualityReviewStatus::Closed)
        ->and($closed->started_at)->not->toBeNull()
        ->and($closed->approved_by)->toBe($this->actor->id)
        ->and($closed->approved_at)->not->toBeNull()
        ->and($closed->closed_at)->not->toBeNull()
        ->and($events)->toHaveCount(4)
        ->and($events->first()->signature_hash)->toBeNull()
        ->and($approvalEvent?->signatureMeaning())->toBe(ProductQualityReviewStatus::Approved->value)
        ->and($approvalEvent?->signatureIpAddress())->toBe('203.0.113.55')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($approvalEvent))->toBeTrue()
        ->and($events->last()->signature_hash)->not->toBeNull();

    expect(fn () => $approvalEvent?->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('requires coherent start data conclusions and independent approval', function (): void {
    $service = app(ProductQualityReviewTransitionService::class);
    $this->review->update(['owner_id' => null]);

    expect(fn () => $service->transition(
        $this->review,
        ProductQualityReviewStatus::InProgress,
        $this->actor,
        'Missing owner.',
    ))->toThrow(ValidationException::class);

    $this->review->update([
        'owner_id' => $this->owner->id,
        'status' => ProductQualityReviewStatus::UnderReview,
        'input_summary' => 'Inputs reviewed.',
    ]);

    expect(fn () => $service->transition(
        $this->review,
        ProductQualityReviewStatus::Approved,
        $this->actor,
        'Missing conclusions.',
    ))->toThrow(ValidationException::class);

    $this->review->update([
        'conclusions' => 'Acceptable.',
        'recommendations' => 'Continue.',
        'owner_id' => $this->actor->id,
    ]);

    expect(fn () => $service->transition(
        $this->review,
        ProductQualityReviewStatus::Approved,
        $this->actor,
        'Owner self-approval.',
    ))->toThrow(ValidationException::class)
        ->and(ProductQualityReviewEvent::query()->count())->toBe(0);
});

it('requires permissions and module entitlement', function (): void {
    $service = app(ProductQualityReviewTransitionService::class);
    $this->review->update([
        'status' => ProductQualityReviewStatus::UnderReview,
        'input_summary' => 'Inputs reviewed.',
        'conclusions' => 'Acceptable.',
        'recommendations' => 'Continue.',
    ]);
    $unpermitted = User::factory()->create();

    expect(fn () => $service->transition(
        $this->review,
        ProductQualityReviewStatus::Approved,
        $unpermitted,
        'Missing permission.',
    ))->toThrow(AuthorizationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->review,
        ProductQualityReviewStatus::Approved,
        $this->actor,
        'Disabled.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(ProductQualityReviewEvent::query()->count())->toBe(0);
});

it('counts period quality inputs by product name match', function (): void {
    $service = app(ProductQualityReviewTransitionService::class);
    $periodStart = $this->review->period_start_at;
    $periodEnd = $this->review->period_end_at;

    Deviation::factory()->create([
        'title' => 'Batch failure for Test Product Alpha',
        'occurred_at' => $periodStart->copy()->addMonths(2),
        'discovered_at' => $periodStart->copy()->addMonths(2),
    ]);
    Complaint::factory()->create([
        'product_name' => 'Test Product Alpha',
        'received_at' => $periodStart->copy()->addMonths(3),
    ]);
    Complaint::factory()->create([
        'product_name' => 'Other Product',
        'received_at' => $periodStart->copy()->addMonths(3),
    ]);

    $counts = $service->periodInputCounts($this->review);

    expect($counts['deviations'])->toBe(1)
        ->and($counts['complaints'])->toBe(1)
        ->and($counts['change_controls'])->toBe(0);
});
