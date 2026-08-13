<?php

declare(strict_types=1);

use App\Filament\Resources\Designations\Pages\ManageDesignations;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach ([
        'ViewAny:Designation',
        'View:Designation',
        'Create:Designation',
        'Update:Designation',
        'Delete:Designation',
        'DeleteAny:Designation',
        'ViewAny:User',
        'View:User',
        'Create:User',
        'Update:User',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role = Role::findOrCreate('sop administrator', 'web');

    $this->user = User::factory()->create();
    $this->user->assignRole($role);
    $this->user->givePermissionTo([
        'ViewAny:Designation',
        'View:Designation',
        'Create:Designation',
        'Update:Designation',
        'Delete:Designation',
        'DeleteAny:Designation',
        'ViewAny:User',
        'View:User',
        'Create:User',
        'Update:User',
    ]);

    $this->actingAs($this->user);
});

it('lists designations for authorized users', function (): void {
    $designation = Designation::factory()->create([
        'name' => 'QA Manager',
        'code' => 'QA_MGR',
    ]);

    Livewire::test(ManageDesignations::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$designation]);
});

it('creates a designation from the manage page', function (): void {
    Livewire::test(ManageDesignations::class)
        ->callAction('create', [
            'name' => 'Document Controller',
            'code' => 'DOC_CTRL',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect(Designation::query()->where('code', 'DOC_CTRL')->exists())->toBeTrue();
});

it('assigns a designation when creating a user', function (): void {
    $designation = Designation::factory()->create([
        'name' => 'Chemist',
        'code' => 'CHEMIST',
    ]);

    $role = Role::findOrCreate('sop maker', 'web');

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Jane Chemist',
            'email' => 'jane.chemist@example.com',
            'password' => 'password',
            'designation_id' => $designation->id,
            'roles' => [$role->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $createdUser = User::query()->where('email', 'jane.chemist@example.com')->first();

    expect($createdUser)->not->toBeNull()
        ->and($createdUser->designation_id)->toBe($designation->id)
        ->and($createdUser->designation?->name)->toBe('Chemist');
});

it('updates a user designation', function (): void {
    $designation = Designation::factory()->create([
        'name' => 'Pharmacist',
        'code' => 'PHARM',
    ]);

    $role = Role::findOrCreate('sop maker', 'web');
    $user = User::factory()->create([
        'designation_id' => null,
    ]);
    $user->assignRole($role);

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->fillForm([
            'name' => $user->name,
            'email' => $user->email,
            'designation_id' => $designation->id,
            'roles' => [$role->id],
            'password' => 'password',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($user->fresh()->designation_id)->toBe($designation->id);
});

it('forbids designation management without permission', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageDesignations::class)
        ->assertForbidden();
});
