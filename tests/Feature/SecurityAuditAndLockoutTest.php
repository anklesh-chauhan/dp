<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\SecurityAuditEventType;
use App\Domain\Shared\Services\ElectronicSignatureAuthenticator;
use App\Domain\Shared\Services\UserAccessService;
use App\Models\SecurityAuditEvent;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('records login success, failure, logout, and lockout in an append-only stream', function (): void {
    config()->set('gxp.lockout_attempts', 5);

    $user = User::factory()->create();
    $service = app(UserAccessService::class);

    Event::dispatch(new Login('web', $user, false));
    Event::dispatch(new Logout('web', $user));

    for ($attempt = 0; $attempt < 5; $attempt++) {
        Event::dispatch(new Failed('web', $user, ['email' => $user->email, 'password' => 'wrong']));
    }

    $user = $user->fresh();

    expect($user->isLocked())->toBeTrue()
        ->and($user->failed_login_attempts)->toBe(5)
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::LoginSucceeded)->count())->toBe(1)
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::Logout)->count())->toBe(1)
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::LoginFailed)->count())->toBe(5)
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::AccountLocked)->count())->toBe(1)
        ->and(fn () => $service->assertCanAuthenticate($user))->toThrow(ValidationException::class);

    $admin = User::factory()->create();
    $unlocked = $service->unlock($admin, $user, 'Identity verified in person.');

    expect($unlocked->isLocked())->toBeFalse()
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::AccountUnlocked)->count())->toBe(1);
});

it('counts failed electronic-signature password attempts toward lockout', function (): void {
    config()->set('gxp.lockout_attempts', 3);

    $user = User::factory()->create();
    $authenticator = app(ElectronicSignatureAuthenticator::class);

    for ($attempt = 0; $attempt < 3; $attempt++) {
        expect(fn () => $authenticator->confirm($user->fresh(), 'wrong-password'))
            ->toThrow(ValidationException::class);
    }

    expect($user->fresh()->isLocked())->toBeTrue()
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::SignatureChallengeFailed)->count())->toBe(3)
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::AccountLocked)->count())->toBe(1);
});

it('rejects updates and deletes of security audit events', function (): void {
    $user = User::factory()->create();
    app(UserAccessService::class)->recordSuccessfulLogin($user);
    $event = SecurityAuditEvent::query()->firstOrFail();

    expect(fn () => $event->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class)
        ->and(fn () => $event->delete())
        ->toThrow(LogicException::class)
        ->and(fn () => DB::table('security_audit_events')->where('id', $event->id)->update(['reason' => 'tampered']))
        ->toThrow(QueryException::class, 'append-only');
});

it('uses a GxP idle timeout shorter than two hours', function (): void {
    expect((int) config('session.lifetime'))->toBeLessThan(120)
        ->and((int) config('gxp.idle_timeout_minutes'))->toBeLessThan(120);
});
