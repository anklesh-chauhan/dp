<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ComplaintSource;
use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\ComplaintType;
use App\Domain\QMS\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the dormant complaint intake and regulatory traceability schema', function (): void {
    expect(Schema::hasColumns('complaints', [
        'complaint_number',
        'status',
        'source',
        'type',
        'title',
        'description',
        'external_reference',
        'received_at',
        'received_by',
        'department_id',
        'owner_id',
        'product_name',
        'batch_number',
        'market_country_code',
        'adverse_event_suspected',
        'regulatory_reportable',
        'regulatory_authority',
        'regulatory_report_due_at',
        'regulatory_reported_at',
        'regulatory_reference',
        'response_due_at',
        'acknowledged_at',
        'closed_at',
    ]))->toBeTrue();
});

it('persists complaint intake classification traceability and regulatory fields', function (): void {
    $department = Department::factory()->create();
    $receiver = User::factory()->create();
    $owner = User::factory()->create();

    $complaint = Complaint::factory()->create([
        'status' => ComplaintStatus::UnderAssessment,
        'source' => ComplaintSource::HealthcareProfessional,
        'type' => ComplaintType::AdverseEvent,
        'received_by' => $receiver,
        'department_id' => $department,
        'owner_id' => $owner,
        'product_name' => 'Example Product',
        'batch_number' => 'LOT-2407',
        'market_country_code' => 'in',
        'adverse_event_suspected' => true,
        'regulatory_reportable' => true,
        'regulatory_authority' => 'Example Authority',
        'regulatory_report_due_at' => '2026-08-01',
        'regulatory_reported_at' => '2026-07-27 09:30:00',
        'regulatory_reference' => 'REG-2026-001',
    ])->refresh();

    expect($complaint->complaint_number)->toStartWith('CMP-')
        ->and($complaint->status)->toBe(ComplaintStatus::UnderAssessment)
        ->and($complaint->source)->toBe(ComplaintSource::HealthcareProfessional)
        ->and($complaint->type)->toBe(ComplaintType::AdverseEvent)
        ->and($complaint->receiver?->is($receiver))->toBeTrue()
        ->and($complaint->department?->is($department))->toBeTrue()
        ->and($complaint->owner?->is($owner))->toBeTrue()
        ->and($complaint->product_name)->toBe('Example Product')
        ->and($complaint->batch_number)->toBe('LOT-2407')
        ->and($complaint->market_country_code)->toBe('IN')
        ->and($complaint->adverse_event_suspected)->toBeTrue()
        ->and($complaint->regulatory_reportable)->toBeTrue()
        ->and($complaint->regulatory_report_due_at?->toDateString())->toBe('2026-08-01')
        ->and($complaint->regulatory_reported_at?->format('Y-m-d H:i:s'))->toBe('2026-07-27 09:30:00')
        ->and($complaint->regulatory_reference)->toBe('REG-2026-001');
});

it('keeps unassessed complaint safety and reportability decisions unknown', function (): void {
    $complaint = Complaint::factory()->create()->refresh();

    expect($complaint->adverse_event_suspected)->toBeNull()
        ->and($complaint->regulatory_reportable)->toBeNull();
});

it('owns complaint permissions without exposing an incomplete resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:Complaint',
            'View:Complaint',
            'Create:Complaint',
            'Update:Complaint',
            'Assess:Complaint',
            'Investigate:Complaint',
            'Respond:Complaint',
            'Close:Complaint',
            'Manage:Complaint',
        )
        ->and(class_exists('App\\Filament\\Resources\\Complaints\\ComplaintResource'))
        ->toBeFalse();
});
