<?php

declare(strict_types=1);

use App\Filament\Resources\Roles\Pages\EditRole;
use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    config()->set('modules.enabled', ['dms', 'qms']);
    $this->seed(DatabaseSeeder::class);
});

it('lists Decide:QualityApproval on the Shield custom permission tab', function (): void {
    expect(FilamentShield::getCustomPermissions())
        ->toHaveKey('Decide:QualityApproval')
        ->toHaveKey('Decide:DocumentTemplateApproval');
});

it('keeps Decide:QualityApproval on the checker role when the role form is saved', function (): void {
    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $checkerRole = Role::findByName('sop checker', 'web');

    $this->actingAs($admin);

    Livewire::test(EditRole::class, ['record' => $checkerRole->getKey()])
        ->assertSuccessful()
        ->assertFormSet(function (array $state): array {
            expect($state['custom_permissions_tab'] ?? [])
                ->toContain('Decide:QualityApproval');

            return [];
        })
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($checkerRole->fresh()->hasPermissionTo('Decide:QualityApproval'))->toBeTrue();
});
