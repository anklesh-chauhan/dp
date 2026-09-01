<?php

declare(strict_types=1);

use App\Domain\QMS\Models\CompetencyCurriculum;
use App\Domain\QMS\Models\UserCompetency;
use App\Domain\TMS\Enums\UserCompetencyStatus;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the competency matrix schema', function (): void {
    expect(Schema::hasColumns('competency_curricula', [
        'code',
        'name',
        'role_name',
        'sop_role_id',
        'description',
        'requalification_months',
        'gate_key',
        'is_active',
        'created_by',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('competency_curriculum_items', [
            'curriculum_id',
            'controlled_document_id',
            'is_required',
            'sort_order',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('user_competencies', [
            'user_id',
            'curriculum_id',
            'status',
            'trained_at',
            'expires_at',
            'verified_by',
            'verified_at',
        ]))->toBeTrue();
});

it('persists curriculum and user competency fields', function (): void {
    $creator = User::factory()->create();
    $user = User::factory()->create();

    $curriculum = CompetencyCurriculum::factory()->create([
        'code' => 'TEST_CURR',
        'name' => 'Test Curriculum',
        'role_name' => 'Analyst',
        'requalification_months' => 24,
        'is_active' => true,
        'created_by' => $creator->id,
    ]);

    $competency = UserCompetency::factory()->trained()->create([
        'user_id' => $user->id,
        'curriculum_id' => $curriculum->id,
        'assigned_by' => $creator->id,
    ])->refresh();

    expect($curriculum->code)->toBe('TEST_CURR')
        ->and($curriculum->is_active)->toBeTrue()
        ->and($competency->status)->toBe(UserCompetencyStatus::Trained)
        ->and($competency->user?->is($user))->toBeTrue()
        ->and($competency->curriculum?->is($curriculum))->toBeTrue();
});

it('owns competency permissions and exposes filament resources', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:CompetencyCurriculum',
            'View:CompetencyCurriculum',
            'Create:CompetencyCurriculum',
            'Update:CompetencyCurriculum',
            'Assign:CompetencyCurriculum',
            'Manage:CompetencyCurriculum',
            'ViewAny:UserCompetency',
            'View:UserCompetency',
            'Create:UserCompetency',
            'Update:UserCompetency',
            'Assign:UserCompetency',
            'Verify:UserCompetency',
            'Manage:UserCompetency',
        )
        ->and(class_exists('App\\Filament\\Resources\\CompetencyCurricula\\CompetencyCurriculumResource'))
        ->toBeTrue()
        ->and(class_exists('App\\Filament\\Resources\\UserCompetencies\\UserCompetencyResource'))
        ->toBeTrue();
});
