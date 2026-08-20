<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Enums\SecurityAuditEventType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserAccessService
{
    public function __construct(private readonly SecurityAuditRecorder $securityAuditRecorder) {}

    public function assertCanAuthenticate(User $user): void
    {
        if ($user->isDeactivated()) {
            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated.',
            ]);
        }

        if ($user->isLocked()) {
            throw ValidationException::withMessages([
                'email' => 'This account is locked after failed authentication attempts.',
            ]);
        }
    }

    public function passwordHasExpired(User $user): bool
    {
        $expiryDays = (int) config('gxp.password_expiry_days', 90);

        if ($expiryDays <= 0) {
            return false;
        }

        $changedAt = $user->password_changed_at ?? $user->created_at;

        if ($changedAt === null) {
            return true;
        }

        return $changedAt->copy()->addDays($expiryDays)->isPast();
    }

    public function recordSuccessfulLogin(User $user): void
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'last_login_at' => now(),
        ])->save();

        $this->securityAuditRecorder->record(
            type: SecurityAuditEventType::LoginSucceeded,
            actor: $user,
            subject: $user,
        );
    }

    public function recordFailedLogin(?User $user, ?string $email): void
    {
        if ($user instanceof User) {
            $this->incrementFailure($user, 'Unsuccessful login.');
        }

        $this->securityAuditRecorder->record(
            type: SecurityAuditEventType::LoginFailed,
            actor: $user,
            subject: $user,
            subjectEmail: $email,
            reason: 'Unsuccessful login.',
        );
    }

    public function recordLogout(User $user): void
    {
        $this->securityAuditRecorder->record(
            type: SecurityAuditEventType::Logout,
            actor: $user,
            subject: $user,
        );
    }

    public function recordSignatureChallengeFailure(User $user): void
    {
        $this->incrementFailure($user, 'Electronic signature password was incorrect.');

        $this->securityAuditRecorder->record(
            type: SecurityAuditEventType::SignatureChallengeFailed,
            actor: $user,
            subject: $user,
            reason: 'Electronic signature password was incorrect.',
        );
    }

    public function unlock(User $actor, User $user, string $reason): User
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ])->save();

        $this->securityAuditRecorder->record(
            type: SecurityAuditEventType::AccountUnlocked,
            actor: $actor,
            subject: $user,
            reason: $reason,
        );

        return $user->refresh();
    }

    public function deactivate(User $actor, User $user, string $reason): User
    {
        $user->forceFill(['deactivated_at' => now()])->save();

        $this->securityAuditRecorder->record(
            type: SecurityAuditEventType::AccountDeactivated,
            actor: $actor,
            subject: $user,
            reason: $reason,
        );

        return $user->refresh();
    }

    public function reactivate(User $actor, User $user, string $reason): User
    {
        $user->forceFill(['deactivated_at' => null])->save();

        $this->securityAuditRecorder->record(
            type: SecurityAuditEventType::AccountReactivated,
            actor: $actor,
            subject: $user,
            reason: $reason,
        );

        return $user->refresh();
    }

    public function resetPassword(User $actor, User $user, string $temporaryPassword, string $reason): User
    {
        return DB::transaction(function () use ($actor, $user, $temporaryPassword, $reason): User {
            $record = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $this->assertPasswordNotReused($record, $temporaryPassword);
            $this->rememberCurrentPassword($record);

            $record->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'password_changed_at' => now(),
                'failed_login_attempts' => 0,
                'locked_at' => null,
            ])->save();

            $this->prunePasswordHistory($record);

            $this->securityAuditRecorder->record(
                type: SecurityAuditEventType::PasswordReset,
                actor: $actor,
                subject: $record,
                reason: $reason,
            );

            return $record->refresh();
        });
    }

    public function changeOwnPassword(User $user, string $currentPassword, string $newPassword): User
    {
        if (! Hash::check($currentPassword, (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        return DB::transaction(function () use ($user, $newPassword): User {
            $record = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $this->assertPasswordNotReused($record, $newPassword);
            $this->rememberCurrentPassword($record);

            $record->forceFill([
                'password' => $newPassword,
                'must_change_password' => false,
                'password_changed_at' => now(),
            ])->save();

            $this->prunePasswordHistory($record);

            $this->securityAuditRecorder->record(
                type: SecurityAuditEventType::PasswordChanged,
                actor: $record,
                subject: $record,
                reason: 'User changed their own password.',
            );

            return $record->refresh();
        });
    }

    public function recordRoleChange(User $actor, User $user, array $roleNames): void
    {
        $this->securityAuditRecorder->record(
            type: SecurityAuditEventType::RoleChanged,
            actor: $actor,
            subject: $user,
            reason: 'Assigned roles were updated.',
            context: ['roles' => array_values($roleNames)],
        );
    }

    private function assertPasswordNotReused(User $user, string $newPassword): void
    {
        $limit = max(1, (int) config('gxp.password_history_count', 12));
        $hashes = collect([(string) $user->getAuthPassword()])
            ->merge($user->passwordHistories()->latest('id')->limit($limit)->pluck('password'))
            ->filter()
            ->unique()
            ->values();

        foreach ($hashes as $hash) {
            if (Hash::check($newPassword, (string) $hash)) {
                throw ValidationException::withMessages([
                    'password' => 'This password was used recently and cannot be reused.',
                ]);
            }
        }
    }

    private function rememberCurrentPassword(User $user): void
    {
        $hash = (string) $user->getAuthPassword();
        $latest = $user->passwordHistories()->latest('id')->value('password');

        if ($latest === $hash) {
            return;
        }

        $user->passwordHistories()->create([
            'password' => $hash,
        ]);
    }

    private function prunePasswordHistory(User $user): void
    {
        $limit = max(1, (int) config('gxp.password_history_count', 12));
        $keepIds = $user->passwordHistories()->latest('id')->limit($limit)->pluck('id');

        $user->passwordHistories()->whereNotIn('id', $keepIds)->delete();
    }

    private function incrementFailure(User $user, string $reason): void
    {
        $maxAttempts = (int) config('gxp.lockout_attempts', 5);

        DB::transaction(function () use ($user, $maxAttempts, $reason): void {
            $record = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $attempts = (int) $record->failed_login_attempts + 1;
            $alreadyLocked = $record->locked_at !== null;
            $lockedAt = $record->locked_at;

            if ($attempts >= $maxAttempts && $lockedAt === null) {
                $lockedAt = now();
            }

            $record->forceFill([
                'failed_login_attempts' => $attempts,
                'locked_at' => $lockedAt,
            ])->save();

            if (! $alreadyLocked && $lockedAt !== null) {
                $this->securityAuditRecorder->record(
                    type: SecurityAuditEventType::AccountLocked,
                    actor: $record,
                    subject: $record,
                    reason: $reason,
                    context: ['attempts' => $attempts],
                );
            }
        });
    }
}
