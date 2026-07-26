<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the dormant change control schema', function (): void {
    expect(Schema::hasColumns('change_controls', [
        'change_number',
        'title',
        'description',
        'rationale',
        'status',
        'department_id',
        'requested_by',
        'owner_id',
        'submitted_at',
        'approved_at',
        'implementation_due_at',
        'implemented_at',
        'effectiveness_due_at',
        'effectiveness_verified_at',
        'closed_at',
    ]))->toBeTrue();
});

it('persists lifecycle vocabulary and responsibility relationships', function (): void {
    $department = Department::factory()->create();
    $requester = User::factory()->create();
    $owner = User::factory()->create();

    $changeControl = ChangeControl::factory()->create([
        'department_id' => $department,
        'requested_by' => $requester,
        'owner_id' => $owner,
        'status' => ChangeControlStatus::UnderReview,
        'submitted_at' => now(),
        'implementation_due_at' => today()->addMonth(),
    ]);

    expect($changeControl->status)->toBe(ChangeControlStatus::UnderReview)
        ->and($changeControl->submitted_at)->not->toBeNull()
        ->and($changeControl->implementation_due_at)->not->toBeNull()
        ->and($changeControl->department->is($department))->toBeTrue()
        ->and($changeControl->requester?->is($requester))->toBeTrue()
        ->and($changeControl->owner?->is($owner))->toBeTrue();
});

it('has a completed change control Filament resource after the dormant foundation', function (): void {
    expect(class_exists('App\\Filament\\Resources\\ChangeControls\\ChangeControlResource'))
        ->toBeTrue();
});
