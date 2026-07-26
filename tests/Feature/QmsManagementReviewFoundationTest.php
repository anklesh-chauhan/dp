<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ManagementReviewInputType;
use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Enums\ManagementReviewType;
use App\Domain\QMS\Models\ManagementReview;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the dormant management review inputs outputs and milestone schema', function (): void {
    expect(Schema::hasColumns('management_reviews', [
        'review_number', 'type', 'status', 'title', 'period_start_at', 'period_end_at',
        'scheduled_at', 'held_at', 'chair_id', 'coordinator_id', 'created_by', 'approved_by',
        'required_inputs', 'input_summary', 'decisions', 'action_summary',
        'minutes_issued_at', 'approved_at', 'completed_at',
    ]))->toBeTrue();
});

it('persists review period schedule responsibilities required inputs outputs and milestones', function (): void {
    $chair = User::factory()->create();
    $coordinator = User::factory()->create();
    $creator = User::factory()->create();
    $approver = User::factory()->create();
    $requiredInputs = [
        ManagementReviewInputType::AuditResults,
        ManagementReviewInputType::CustomerFeedback,
        ManagementReviewInputType::CapaStatus,
        ManagementReviewInputType::RiskManagement,
    ];

    $review = ManagementReview::factory()->create([
        'type' => ManagementReviewType::Quarterly,
        'status' => ManagementReviewStatus::Completed,
        'period_start_at' => '2026-04-01',
        'period_end_at' => '2026-06-30',
        'scheduled_at' => '2026-07-15 10:00:00',
        'held_at' => '2026-07-15 10:05:00',
        'chair_id' => $chair,
        'coordinator_id' => $coordinator,
        'created_by' => $creator,
        'approved_by' => $approver,
        'required_inputs' => array_map(
            static fn (ManagementReviewInputType $type): string => $type->value,
            $requiredInputs,
        ),
        'input_summary' => 'All required quality-system inputs were reviewed.',
        'decisions' => 'Increase internal-audit sampling for electronic records.',
        'action_summary' => 'Quality Systems will revise the annual audit plan.',
        'minutes_issued_at' => '2026-07-17 09:00:00',
        'approved_at' => '2026-07-18 11:00:00',
        'completed_at' => '2026-07-18 11:05:00',
    ])->refresh();

    expect($review->review_number)->toStartWith('MR-')
        ->and($review->type)->toBe(ManagementReviewType::Quarterly)
        ->and($review->status)->toBe(ManagementReviewStatus::Completed)
        ->and($review->period_start_at?->toDateString())->toBe('2026-04-01')
        ->and($review->period_end_at?->toDateString())->toBe('2026-06-30')
        ->and($review->chair?->is($chair))->toBeTrue()
        ->and($review->coordinator?->is($coordinator))->toBeTrue()
        ->and($review->creator?->is($creator))->toBeTrue()
        ->and($review->approver?->is($approver))->toBeTrue()
        ->and($review->requiredInputTypes())->toBe($requiredInputs)
        ->and($review->minutes_issued_at?->format('Y-m-d H:i:s'))->toBe('2026-07-17 09:00:00')
        ->and($review->completed_at?->format('Y-m-d H:i:s'))->toBe('2026-07-18 11:05:00');
});

it('owns management review permissions without exposing an incomplete resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:ManagementReview',
            'View:ManagementReview',
            'Create:ManagementReview',
            'Update:ManagementReview',
            'Schedule:ManagementReview',
            'Conduct:ManagementReview',
            'IssueMinutes:ManagementReview',
            'Approve:ManagementReview',
            'Complete:ManagementReview',
            'Manage:ManagementReview',
        )
        ->and(class_exists('App\\Filament\\Resources\\ManagementReviews\\ManagementReviewResource'))
        ->toBeFalse();
});
