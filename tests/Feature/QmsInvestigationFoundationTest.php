<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\Investigation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the dormant investigation schema', function (): void {
    expect(Schema::hasColumns('investigations', [
        'investigation_number',
        'deviation_id',
        'status',
        'lead_id',
        'methodology',
        'root_cause',
        'conclusion',
        'started_at',
        'due_at',
        'completed_at',
    ]))->toBeTrue();
});

it('persists a linked investigation lifecycle and lead responsibility', function (): void {
    $deviation = Deviation::factory()->create();
    $lead = User::factory()->create();
    $investigation = Investigation::factory()->create([
        'deviation_id' => $deviation,
        'lead_id' => $lead,
        'status' => InvestigationStatus::Completed,
        'root_cause' => 'Preventive maintenance interval was not risk-based.',
        'conclusion' => 'Revise the maintenance strategy and verify effectiveness.',
        'started_at' => now()->subDays(5),
        'completed_at' => now(),
    ]);

    expect($investigation->investigation_number)->toStartWith('INV-')
        ->and($investigation->status)->toBe(InvestigationStatus::Completed)
        ->and($investigation->deviation->is($deviation))->toBeTrue()
        ->and($investigation->lead?->is($lead))->toBeTrue()
        ->and($deviation->investigations()->first()?->is($investigation))->toBeTrue()
        ->and($investigation->root_cause)->not->toBeNull()
        ->and($investigation->completed_at)->not->toBeNull();
});

it('exposes the completed investigation Filament resource', function (): void {
    expect(class_exists('App\\Filament\\Resources\\Investigations\\InvestigationResource'))
        ->toBeTrue();
});
