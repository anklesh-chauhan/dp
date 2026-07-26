<?php

declare(strict_types=1);

use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Services\DeviationSubmissionAuthorization;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['Update:Deviation', 'Submit:Deviation', 'Manage:Deviation'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
});

it('allows an attributed reporter or owner with submission permission', function (string $attribution): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department]);
    $user->givePermissionTo('Submit:Deviation');
    $deviation = Deviation::factory()->create([
        'department_id' => $department,
        'reported_by' => $attribution === 'reporter' ? $user : User::factory(),
        'owner_id' => $attribution === 'owner' ? $user : User::factory(),
    ]);

    expect(app(DeviationSubmissionAuthorization::class)->canSubmit($deviation, $user))
        ->toBeTrue();
})->with(['reporter', 'owner']);

it('rejects missing permission attribution and cross-department access', function (
    bool $hasPermission,
    bool $isReporter,
    bool $sameDepartment,
): void {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'department_id' => $sameDepartment ? $department : Department::factory(),
    ]);

    if ($hasPermission) {
        $user->givePermissionTo('Submit:Deviation');
    }

    $deviation = Deviation::factory()->create([
        'department_id' => $department,
        'reported_by' => $isReporter ? $user : User::factory(),
        'owner_id' => User::factory(),
    ]);

    expect(app(DeviationSubmissionAuthorization::class)->canSubmit($deviation, $user))
        ->toBeFalse();
})->with([
    'no permission' => [false, true, true],
    'no attribution' => [true, false, true],
    'different department' => [true, true, false],
]);

it('allows a deviation manager regardless of attribution and department', function (): void {
    $manager = User::factory()->create(['department_id' => Department::factory()]);
    $manager->givePermissionTo(['Submit:Deviation', 'Manage:Deviation']);
    $deviation = Deviation::factory()->create([
        'department_id' => Department::factory(),
        'reported_by' => User::factory(),
        'owner_id' => User::factory(),
    ]);

    expect(app(DeviationSubmissionAuthorization::class)->canSubmit($deviation, $manager))
        ->toBeTrue();
});
