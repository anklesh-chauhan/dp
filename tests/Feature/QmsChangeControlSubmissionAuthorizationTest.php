<?php

declare(strict_types=1);

use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Services\ChangeControlSubmissionAuthorization;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['Update:ChangeControl', 'Submit:ChangeControl', 'Manage:ChangeControl'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
});

it('allows an attributed requester or owner with submission permission', function (string $attribution): void {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department]);
    $user->givePermissionTo('Submit:ChangeControl');
    $changeControl = ChangeControl::factory()->create([
        'department_id' => $department,
        'requested_by' => $attribution === 'requester' ? $user : User::factory(),
        'owner_id' => $attribution === 'owner' ? $user : User::factory(),
    ]);

    expect(app(ChangeControlSubmissionAuthorization::class)->canSubmit($changeControl, $user))
        ->toBeTrue();
})->with(['requester', 'owner']);

it('rejects users without permission or attribution and rejects cross-department access', function (
    bool $hasPermission,
    bool $isRequester,
    bool $sameDepartment,
): void {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'department_id' => $sameDepartment
            ? $department
            : Department::factory(),
    ]);

    if ($hasPermission) {
        $user->givePermissionTo('Submit:ChangeControl');
    }

    $changeControl = ChangeControl::factory()->create([
        'department_id' => $department,
        'requested_by' => $isRequester ? $user : User::factory(),
        'owner_id' => User::factory(),
    ]);

    expect(app(ChangeControlSubmissionAuthorization::class)->canSubmit($changeControl, $user))
        ->toBeFalse();
})->with([
    'no permission' => [false, true, true],
    'no attribution' => [true, false, true],
    'different department' => [true, true, false],
]);

it('allows a QMS manager with permission regardless of attribution and department', function (): void {
    $manager = User::factory()->create([
        'department_id' => Department::factory(),
    ]);
    $manager->givePermissionTo([
        'Submit:ChangeControl',
        'Manage:ChangeControl',
    ]);
    $changeControl = ChangeControl::factory()->create([
        'department_id' => Department::factory(),
        'requested_by' => User::factory(),
        'owner_id' => User::factory(),
    ]);

    expect(app(ChangeControlSubmissionAuthorization::class)->canSubmit($changeControl, $manager))
        ->toBeTrue();
});
