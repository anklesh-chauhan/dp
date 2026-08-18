<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ComputerizedSystemIncidentStatus;
use App\Domain\QMS\Models\ComputerizedSystemIncident;
use App\Domain\QMS\Services\ComputerizedSystemIncidentTransitionService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Filament\Resources\ComputerizedSystemIncidents\ComputerizedSystemIncidentResource;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'Investigate:ComputerizedSystemIncident',
        'Close:ComputerizedSystemIncident',
        'Manage:ComputerizedSystemIncident',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->incident = ComputerizedSystemIncident::factory()->create([
        'owner_id' => $this->actor->id,
        'created_by' => $this->actor->id,
    ]);
});

it('installs the computerized system incident schema and permissions', function (): void {
    expect(Schema::hasColumns('computerized_system_incidents', [
        'incident_number',
        'title',
        'status',
        'severity',
        'category',
        'description',
        'impact',
        'owner_id',
        'created_by',
        'detected_at',
        'resolved_at',
        'closed_at',
    ]))->toBeTrue()
        ->and(Schema::hasTable('computerized_system_incident_events'))->toBeTrue()
        ->and(QmsModuleSeeder::PERMISSIONS)->toContain(
            'ViewAny:ComputerizedSystemIncident',
            'Investigate:ComputerizedSystemIncident',
            'Close:ComputerizedSystemIncident',
            'Manage:ComputerizedSystemIncident',
        )
        ->and(ComputerizedSystemIncidentResource::getNavigationGroup())->toBe('QMS')
        ->and(ComputerizedSystemIncidentResource::getNavigationSort())->toBe(25);
});

it('records signed resolve and close decisions with append-only history', function (): void {
    $service = app(ComputerizedSystemIncidentTransitionService::class);
    $service->transition(
        $this->incident,
        ComputerizedSystemIncidentStatus::Investigating,
        $this->actor,
        'Investigation started after an availability alert.',
    );
    $resolved = $service->transition(
        $this->incident,
        ComputerizedSystemIncidentStatus::Resolved,
        $this->actor,
        'Service restored and data integrity verified.',
        ipAddress: '203.0.113.40',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );
    $closed = $service->transition(
        $resolved,
        ComputerizedSystemIncidentStatus::Closed,
        $this->actor,
        'QA accepted the restoration evidence.',
        ipAddress: '203.0.113.40',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );

    $signedEvent = $closed->auditEvents()->orderBy('id')->get()->last();

    expect($closed->status)->toBe(ComputerizedSystemIncidentStatus::Closed)
        ->and($closed->resolved_at)->not->toBeNull()
        ->and($closed->closed_at)->not->toBeNull()
        ->and($signedEvent->signatureMeaning())->toBe(ComputerizedSystemIncidentStatus::Closed->value)
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signedEvent))->toBeTrue();

    expect(fn () => $signedEvent->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class)
        ->and(fn () => $signedEvent->delete())
        ->toThrow(LogicException::class);
});

it('requires a reason for every incident transition', function (): void {
    expect(fn () => app(ComputerizedSystemIncidentTransitionService::class)->transition(
        $this->incident,
        ComputerizedSystemIncidentStatus::Investigating,
        $this->actor,
        ' ',
    ))->toThrow(ValidationException::class);
});
