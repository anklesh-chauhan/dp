<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\LaboratoryOosPhaseOutcome;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Enums\LaboratoryOosType;
use App\Domain\QMS\Models\LaboratoryOosEvent;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the laboratory oos event and append-only event schema', function (): void {
    expect(Schema::hasColumns('laboratory_oos_events', [
        'event_number',
        'type',
        'status',
        'title',
        'test_name',
        'method_reference',
        'sample_id',
        'batch_number',
        'product_name',
        'specification_limit',
        'observed_result',
        'unit',
        'phase_one_outcome',
        'phase_one_notes',
        'phase_two_outcome',
        'phase_two_notes',
        'hypothesis',
        'invalidation_justification',
        'investigation_id',
        'deviation_id',
        'department_id',
        'owner_id',
        'created_by',
        'analyst_id',
        'started_at',
        'phase_one_completed_at',
        'phase_two_completed_at',
        'closed_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('laboratory_oos_event_events', [
            'event_uuid',
            'laboratory_oos_event_id',
            'from_status',
            'to_status',
            'actor_id',
            'reason',
            'context',
            'signature_hash',
            'signature_ip_address',
            'signature_user_agent',
            'occurred_at',
        ]))->toBeTrue();
});

it('persists laboratory oos classification ownership and result traceability', function (): void {
    $department = Department::factory()->create();
    $owner = User::factory()->create();
    $creator = User::factory()->create();
    $analyst = User::factory()->create();

    $event = LaboratoryOosEvent::factory()->create([
        'type' => LaboratoryOosType::Oot,
        'status' => LaboratoryOosStatus::PhaseOne,
        'department_id' => $department,
        'owner_id' => $owner,
        'created_by' => $creator,
        'analyst_id' => $analyst,
        'test_name' => 'Assay HPLC',
        'product_name' => 'Example Product',
        'batch_number' => 'LOT-2407',
        'observed_result' => '105.2',
        'unit' => '%',
        'phase_one_outcome' => LaboratoryOosPhaseOutcome::Inconclusive,
    ])->refresh();

    expect($event->event_number)->toStartWith('OOT-')
        ->and($event->type)->toBe(LaboratoryOosType::Oot)
        ->and($event->status)->toBe(LaboratoryOosStatus::PhaseOne)
        ->and($event->department?->is($department))->toBeTrue()
        ->and($event->owner?->is($owner))->toBeTrue()
        ->and($event->creator?->is($creator))->toBeTrue()
        ->and($event->analyst?->is($analyst))->toBeTrue()
        ->and($event->test_name)->toBe('Assay HPLC')
        ->and($event->product_name)->toBe('Example Product')
        ->and($event->batch_number)->toBe('LOT-2407')
        ->and($event->observed_result)->toBe('105.2')
        ->and($event->unit)->toBe('%')
        ->and($event->phase_one_outcome)->toBe(LaboratoryOosPhaseOutcome::Inconclusive)
        ->and($event->attachments())->toBeTruthy();
});

it('owns laboratory oos permissions and exposes the Filament resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:LaboratoryOosEvent',
            'View:LaboratoryOosEvent',
            'Create:LaboratoryOosEvent',
            'Update:LaboratoryOosEvent',
            'Investigate:LaboratoryOosEvent',
            'Confirm:LaboratoryOosEvent',
            'Close:LaboratoryOosEvent',
            'Manage:LaboratoryOosEvent',
        )
        ->and(class_exists('App\\Filament\\Resources\\LaboratoryOosEvents\\LaboratoryOosEventResource'))
        ->toBeTrue();
});
