<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\CapaType;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\Investigation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the dormant CAPA schema', function (): void {
    expect(Schema::hasColumns('capas', [
        'capa_number',
        'deviation_id',
        'investigation_id',
        'type',
        'status',
        'title',
        'action_plan',
        'owner_id',
        'due_at',
        'completed_at',
        'effectiveness_due_at',
        'effectiveness_verified_at',
        'effectiveness_result',
        'closed_at',
    ]))->toBeTrue();
});

it('persists linked CAPA classification lifecycle and effectiveness details', function (): void {
    $deviation = Deviation::factory()->create();
    $investigation = Investigation::factory()->create([
        'deviation_id' => $deviation,
    ]);
    $owner = User::factory()->create();
    $capa = Capa::factory()->create([
        'deviation_id' => $deviation,
        'investigation_id' => $investigation,
        'owner_id' => $owner,
        'type' => CapaType::CorrectiveAndPreventive,
        'status' => CapaStatus::Effective,
        'completed_at' => now()->subMonth(),
        'effectiveness_verified_at' => now(),
        'effectiveness_result' => 'No recurrence during the verification period.',
    ]);

    expect($capa->capa_number)->toStartWith('CAPA-')
        ->and($capa->type)->toBe(CapaType::CorrectiveAndPreventive)
        ->and($capa->status)->toBe(CapaStatus::Effective)
        ->and($capa->deviation->is($deviation))->toBeTrue()
        ->and($capa->investigation?->is($investigation))->toBeTrue()
        ->and($capa->owner?->is($owner))->toBeTrue()
        ->and($deviation->capas()->first()?->is($capa))->toBeTrue()
        ->and($investigation->capas()->first()?->is($capa))->toBeTrue()
        ->and($capa->effectiveness_verified_at)->not->toBeNull();
});

it('exposes the completed CAPA Filament resource', function (): void {
    expect(class_exists('App\\Filament\\Resources\\Capas\\CapaResource'))
        ->toBeTrue();
});
