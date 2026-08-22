<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms']);
    config()->set('gxp.mfa_required', false);
});

it('renders the admin login page', function (): void {
    $this->get('/admin/login')
        ->assertSuccessful()
        ->assertDontSee('Page Expired');
});

it('authenticates a panel user from the admin login page', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('panel_user', 'web'));

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

it('rejects an invalid password without expiring the login page', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('panel_user', 'web'));

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'not-the-password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email'])
        ->assertNoRedirect();

    $this->assertGuest();
});
