<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Enums\InternalAuditType;
use App\Domain\QMS\Models\InternalAudit;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the dormant internal audit scope schedule and milestone schema', function (): void {
    expect(Schema::hasColumns('internal_audits', [
        'audit_number',
        'type',
        'status',
        'title',
        'scope',
        'objectives',
        'criteria',
        'department_id',
        'lead_auditor_id',
        'created_by',
        'owner_id',
        'scheduled_start_at',
        'scheduled_end_at',
        'started_at',
        'completed_at',
        'report_issued_at',
        'follow_up_due_at',
        'closed_at',
    ]))->toBeTrue();
});

it('persists audit classification scope schedule attribution and reporting milestones', function (): void {
    $department = Department::factory()->create();
    $leadAuditor = User::factory()->create();
    $creator = User::factory()->create();
    $owner = User::factory()->create();

    $audit = InternalAudit::factory()->create([
        'type' => InternalAuditType::RegulatoryReadiness,
        'status' => InternalAuditStatus::FollowUp,
        'title' => 'Data integrity readiness audit',
        'scope' => 'Electronic records and attributable approval controls.',
        'department_id' => $department,
        'lead_auditor_id' => $leadAuditor,
        'created_by' => $creator,
        'owner_id' => $owner,
        'scheduled_start_at' => '2026-08-10',
        'scheduled_end_at' => '2026-08-12',
        'started_at' => '2026-08-10 09:00:00',
        'completed_at' => '2026-08-12 17:30:00',
        'report_issued_at' => '2026-08-15 10:00:00',
        'follow_up_due_at' => '2026-09-15',
    ])->refresh();

    expect($audit->audit_number)->toStartWith('AUD-')
        ->and($audit->type)->toBe(InternalAuditType::RegulatoryReadiness)
        ->and($audit->status)->toBe(InternalAuditStatus::FollowUp)
        ->and($audit->department?->is($department))->toBeTrue()
        ->and($audit->leadAuditor?->is($leadAuditor))->toBeTrue()
        ->and($audit->creator?->is($creator))->toBeTrue()
        ->and($audit->owner?->is($owner))->toBeTrue()
        ->and($audit->scheduled_start_at?->toDateString())->toBe('2026-08-10')
        ->and($audit->scheduled_end_at?->toDateString())->toBe('2026-08-12')
        ->and($audit->report_issued_at?->format('Y-m-d H:i:s'))->toBe('2026-08-15 10:00:00')
        ->and($audit->follow_up_due_at?->toDateString())->toBe('2026-09-15');
});

it('owns internal audit permissions without exposing an incomplete resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:InternalAudit',
            'View:InternalAudit',
            'Create:InternalAudit',
            'Update:InternalAudit',
            'Schedule:InternalAudit',
            'Conduct:InternalAudit',
            'Report:InternalAudit',
            'FollowUp:InternalAudit',
            'Close:InternalAudit',
            'Manage:InternalAudit',
        )
        ->and(class_exists('App\\Filament\\Resources\\InternalAudits\\InternalAuditResource'))
        ->toBeFalse();
});
