<?php

declare(strict_types=1);

use App\Domain\Shared\Services\UserAccessService;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('assigns a durable identity uuid that is not reused', function (): void {
    $user = User::factory()->create();

    expect($user->identity_uuid)->not->toBeEmpty()
        ->and(Str::isUuid($user->identity_uuid))->toBeTrue();
});

it('never reuses an email address even after deactivation', function (): void {
    $user = User::factory()->create(['email' => 'qa.user@example.com']);
    app(UserAccessService::class)->deactivate(User::factory()->create(), $user, 'Left the company.');

    expect(fn () => User::factory()->create(['email' => 'qa.user@example.com']))
        ->toThrow(QueryException::class);
});

it('blocks authentication for deactivated and locked accounts', function (): void {
    $service = app(UserAccessService::class);
    $deactivated = User::factory()->deactivated()->create();
    $locked = User::factory()->locked()->create();

    expect(fn () => $service->assertCanAuthenticate($deactivated))->toThrow(ValidationException::class)
        ->and(fn () => $service->assertCanAuthenticate($locked))->toThrow(ValidationException::class)
        ->and($deactivated->fresh()->isDeactivated())->toBeTrue()
        ->and($locked->fresh()->isLocked())->toBeTrue();
});

it('issues an admin password reset that requires a first-login change', function (): void {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $reset = app(UserAccessService::class)->resetPassword(
        $admin,
        $user,
        'Temporary12!x',
        'User forgot the password.',
    );

    expect($reset->must_change_password)->toBeTrue()
        ->and(Hash::check('Temporary12!x', $reset->password))->toBeTrue()
        ->and($reset->mustChangePassword())->toBeTrue();
});

it('treats expired passwords as requiring a change', function (): void {
    $user = User::factory()->create();
    $user->forceFill(['password_changed_at' => now()->subDays(91)])->save();

    expect($user->fresh()->mustChangePassword())->toBeTrue();
});

it('forbids deleting user accounts', function (): void {
    $actor = User::factory()->create();

    expect((new UserPolicy)->delete($actor))->toBeFalse()
        ->and((new UserPolicy)->deleteAny($actor))->toBeFalse();
});
