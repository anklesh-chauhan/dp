<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the dormant deviation schema', function (): void {
    expect(Schema::hasColumns('deviations', [
        'deviation_number',
        'title',
        'description',
        'immediate_actions',
        'status',
        'severity',
        'occurred_at',
        'discovered_at',
        'department_id',
        'reported_by',
        'owner_id',
        'investigation_due_at',
        'closed_at',
    ]))->toBeTrue();
});

it('persists deviation classification lifecycle and responsibility', function (): void {
    $department = Department::factory()->create();
    $reporter = User::factory()->create();
    $owner = User::factory()->create();

    $deviation = Deviation::factory()->create([
        'department_id' => $department,
        'reported_by' => $reporter,
        'owner_id' => $owner,
        'status' => DeviationStatus::UnderInvestigation,
        'severity' => DeviationSeverity::Critical,
    ]);

    expect($deviation->deviation_number)->toStartWith('DEV-')
        ->and($deviation->status)->toBe(DeviationStatus::UnderInvestigation)
        ->and($deviation->severity)->toBe(DeviationSeverity::Critical)
        ->and($deviation->occurred_at)->not->toBeNull()
        ->and($deviation->discovered_at)->not->toBeNull()
        ->and($deviation->department->is($department))->toBeTrue()
        ->and($deviation->reporter?->is($reporter))->toBeTrue()
        ->and($deviation->owner?->is($owner))->toBeTrue();
});

it('exposes the completed deviation Filament resource', function (): void {
    expect(class_exists('App\\Filament\\Resources\\Deviations\\DeviationResource'))
        ->toBeTrue();
});
