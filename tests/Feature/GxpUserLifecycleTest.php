<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\SecurityAuditEventType;
use App\Domain\Shared\Services\UserAccessService;
use App\Models\PasswordHistory;
use App\Models\SecurityAuditEvent;
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

it('rejects reuse of the current password and recent history hashes', function (): void {
    $service = app(UserAccessService::class);
    $user = User::factory()->create();

    expect(fn () => $service->changeOwnPassword($user, 'password', 'password'))
        ->toThrow(ValidationException::class)
        ->and($user->passwordHistories()->count())->toBe(1);

    $changed = $service->changeOwnPassword($user, 'password', 'Temporary12!a');

    expect(Hash::check('Temporary12!a', $changed->password))->toBeTrue()
        ->and(fn () => $service->changeOwnPassword($changed, 'Temporary12!a', 'Temporary12!a'))
        ->toThrow(ValidationException::class)
        ->and(fn () => $service->changeOwnPassword($changed->fresh(), 'Temporary12!a', 'password'))
        ->toThrow(ValidationException::class)
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::PasswordChanged)->count())->toBe(1);
});

it('allows a password after it falls off the configured history window', function (): void {
    config()->set('gxp.password_history_count', 2);

    $service = app(UserAccessService::class);
    $user = User::factory()->create();
    $service->changeOwnPassword($user, 'password', 'Temporary12!a');
    $service->changeOwnPassword($user->fresh(), 'Temporary12!a', 'Temporary12!b');
    $service->changeOwnPassword($user->fresh(), 'Temporary12!b', 'Temporary12!c');

    $rotated = $service->changeOwnPassword($user->fresh(), 'Temporary12!c', 'password');

    expect(Hash::check('password', $rotated->password))->toBeTrue()
        ->and($rotated->passwordHistories()->count())->toBe(2);
});

it('prevents admin resets from reusing the last n passwords', function (): void {
    $admin = User::factory()->create();
    $user = User::factory()->create();
    $service = app(UserAccessService::class);

    expect(fn () => $service->resetPassword($admin, $user, 'password', 'Attempted reuse of issued password.'))
        ->toThrow(ValidationException::class);

    $reset = $service->resetPassword($admin, $user, 'Temporary12!x', 'User forgot the password.');

    expect($reset->must_change_password)->toBeTrue()
        ->and(Hash::check('Temporary12!x', $reset->password))->toBeTrue()
        ->and(fn () => $service->resetPassword($admin, $reset, 'Temporary12!x', 'Reuse of last reset.'))
        ->toThrow(ValidationException::class)
        ->and(PasswordHistory::query()->where('user_id', $user->id)->count())->toBeGreaterThanOrEqual(1)
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::PasswordReset)->count())->toBe(1);
});
