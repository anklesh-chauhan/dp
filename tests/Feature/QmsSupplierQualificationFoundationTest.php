<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\SupplierCategory;
use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Enums\SupplierRiskLevel;
use App\Domain\QMS\Models\SupplierQualification;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the dormant supplier identity qualification and review schema', function (): void {
    expect(Schema::hasColumns('supplier_qualifications', [
        'supplier_number', 'legal_name', 'site_name', 'category', 'status', 'risk_level',
        'material_service_scope', 'country_code', 'site_address', 'contact_name',
        'contact_email', 'contact_phone', 'department_id', 'owner_id', 'created_by',
        'approved_by', 'qualification_rationale', 'qualification_started_at',
        'audit_due_at', 'audit_completed_at', 'qualified_at', 'qualification_expires_at',
        'next_review_at', 'suspended_at', 'disqualified_at',
    ]))->toBeTrue();
});

it('persists supplier identity scope risk responsibility audit and qualification milestones', function (): void {
    $department = Department::factory()->create();
    $owner = User::factory()->create();
    $creator = User::factory()->create();
    $approver = User::factory()->create();

    $qualification = SupplierQualification::factory()->create([
        'legal_name' => 'Example Active Ingredients Limited',
        'site_name' => 'Pune API Site',
        'category' => SupplierCategory::RawMaterial,
        'status' => SupplierQualificationStatus::Qualified,
        'risk_level' => SupplierRiskLevel::Critical,
        'material_service_scope' => 'Active pharmaceutical ingredient manufacture and release.',
        'country_code' => 'in',
        'department_id' => $department,
        'owner_id' => $owner,
        'created_by' => $creator,
        'approved_by' => $approver,
        'qualification_rationale' => 'Audit observations were resolved before approval.',
        'qualification_started_at' => '2026-08-01 09:00:00',
        'audit_due_at' => '2026-08-15',
        'audit_completed_at' => '2026-08-14 17:00:00',
        'qualified_at' => '2026-08-20 10:00:00',
        'qualification_expires_at' => '2027-08-20',
        'next_review_at' => '2027-02-20',
    ])->refresh();

    expect($qualification->supplier_number)->toStartWith('SUP-')
        ->and($qualification->category)->toBe(SupplierCategory::RawMaterial)
        ->and($qualification->status)->toBe(SupplierQualificationStatus::Qualified)
        ->and($qualification->risk_level)->toBe(SupplierRiskLevel::Critical)
        ->and($qualification->country_code)->toBe('IN')
        ->and($qualification->department?->is($department))->toBeTrue()
        ->and($qualification->owner?->is($owner))->toBeTrue()
        ->and($qualification->creator?->is($creator))->toBeTrue()
        ->and($qualification->approver?->is($approver))->toBeTrue()
        ->and($qualification->audit_completed_at?->format('Y-m-d H:i:s'))->toBe('2026-08-14 17:00:00')
        ->and($qualification->qualification_expires_at?->toDateString())->toBe('2027-08-20')
        ->and($qualification->next_review_at?->toDateString())->toBe('2027-02-20');
});

it('owns supplier qualification permissions without exposing an incomplete resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:SupplierQualification',
            'View:SupplierQualification',
            'Create:SupplierQualification',
            'Update:SupplierQualification',
            'Assess:SupplierQualification',
            'Audit:SupplierQualification',
            'Approve:SupplierQualification',
            'Suspend:SupplierQualification',
            'Disqualify:SupplierQualification',
            'Review:SupplierQualification',
            'Manage:SupplierQualification',
        )
        ->and(class_exists('App\\Filament\\Resources\\SupplierQualifications\\SupplierQualificationResource'))
        ->toBeFalse();
});
