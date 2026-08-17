<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use App\Domain\QMS\Models\ValidationMasterPlan;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the validation master plan schema', function (): void {
    expect(Schema::hasColumns('validation_master_plans', [
        'vmp_number',
        'title',
        'status',
        'period_start_at',
        'period_end_at',
        'scope',
        'owner_id',
        'created_by',
        'approved_by',
        'controlled_document_id',
        'approved_at',
        'retired_at',
    ]))->toBeTrue()
        ->and(Schema::hasTable('validation_master_plan_events'))->toBeTrue();
});

it('persists validation master plan fields', function (): void {
    $owner = User::factory()->create();
    $creator = User::factory()->create();

    $plan = ValidationMasterPlan::factory()->create([
        'title' => 'Site VMP 2026',
        'status' => ValidationMasterPlanStatus::Draft,
        'scope' => 'All GxP equipment and processes.',
        'owner_id' => $owner,
        'created_by' => $creator,
    ])->refresh();

    expect($plan->vmp_number)->toStartWith('VMP-')
        ->and($plan->status)->toBe(ValidationMasterPlanStatus::Draft)
        ->and($plan->owner?->is($owner))->toBeTrue()
        ->and($plan->creator?->is($creator))->toBeTrue();
});

it('owns validation master plan permissions and exposes the Filament resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:ValidationMasterPlan',
            'View:ValidationMasterPlan',
            'Create:ValidationMasterPlan',
            'Update:ValidationMasterPlan',
            'Approve:ValidationMasterPlan',
            'Retire:ValidationMasterPlan',
            'Manage:ValidationMasterPlan',
        )
        ->and(class_exists('App\\Filament\\Resources\\ValidationMasterPlans\\ValidationMasterPlanResource'))
        ->toBeTrue();
});
