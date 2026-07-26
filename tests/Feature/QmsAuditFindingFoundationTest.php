<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\AuditFindingClassification;
use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\AuditFindingSeverity;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\InternalAudit;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the dormant audit finding traceability and disposition schema', function (): void {
    expect(Schema::hasColumns('audit_findings', [
        'finding_number', 'internal_audit_id', 'severity', 'classification', 'disposition',
        'clause_reference', 'title', 'description', 'objective_evidence', 'department_id',
        'owner_id', 'identified_by', 'identified_at', 'response_due_at', 'response',
        'responded_at', 'verified_by', 'verification_notes', 'verified_at', 'closed_at',
    ]))->toBeTrue();
});

it('persists audit linkage classification evidence ownership response and verification milestones', function (): void {
    $audit = InternalAudit::factory()->create();
    $department = Department::factory()->create();
    $owner = User::factory()->create();
    $identifier = User::factory()->create();
    $verifier = User::factory()->create();

    $finding = AuditFinding::factory()->create([
        'internal_audit_id' => $audit,
        'severity' => AuditFindingSeverity::Critical,
        'classification' => AuditFindingClassification::Nonconformity,
        'disposition' => AuditFindingDisposition::Accepted,
        'clause_reference' => '21 CFR 11.10(e)',
        'objective_evidence' => 'Three approval records lacked contemporaneous audit history.',
        'department_id' => $department,
        'owner_id' => $owner,
        'identified_by' => $identifier,
        'identified_at' => '2026-08-12 14:00:00',
        'response_due_at' => '2026-09-12',
        'response' => 'A remediation plan was submitted.',
        'responded_at' => '2026-08-20 10:30:00',
        'verified_by' => $verifier,
        'verification_notes' => 'Response addresses the observed control gap.',
        'verified_at' => '2026-08-22 09:15:00',
    ])->refresh();

    expect($finding->finding_number)->toStartWith('AF-')
        ->and($finding->internalAudit->is($audit))->toBeTrue()
        ->and($audit->findings()->first()?->is($finding))->toBeTrue()
        ->and($finding->severity)->toBe(AuditFindingSeverity::Critical)
        ->and($finding->classification)->toBe(AuditFindingClassification::Nonconformity)
        ->and($finding->disposition)->toBe(AuditFindingDisposition::Accepted)
        ->and($finding->department?->is($department))->toBeTrue()
        ->and($finding->owner?->is($owner))->toBeTrue()
        ->and($finding->identifier?->is($identifier))->toBeTrue()
        ->and($finding->verifier?->is($verifier))->toBeTrue()
        ->and($finding->response_due_at?->toDateString())->toBe('2026-09-12')
        ->and($finding->verified_at?->format('Y-m-d H:i:s'))->toBe('2026-08-22 09:15:00');
});

it('owns audit finding permissions without exposing an incomplete resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:AuditFinding',
            'View:AuditFinding',
            'Create:AuditFinding',
            'Update:AuditFinding',
            'Respond:AuditFinding',
            'Verify:AuditFinding',
            'Close:AuditFinding',
            'Manage:AuditFinding',
        )
        ->and(class_exists('App\\Filament\\Resources\\AuditFindings\\AuditFindingResource'))
        ->toBeFalse();
});
