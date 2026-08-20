<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum SecurityAuditEventType: string
{
    case LoginSucceeded = 'login_succeeded';
    case LoginFailed = 'login_failed';
    case Logout = 'logout';
    case AccountLocked = 'account_locked';
    case AccountUnlocked = 'account_unlocked';
    case AccountDeactivated = 'account_deactivated';
    case AccountReactivated = 'account_reactivated';
    case PasswordChanged = 'password_changed';
    case PasswordReset = 'password_reset';
    case SignatureChallengeFailed = 'signature_challenge_failed';
    case RoleChanged = 'role_changed';
    case BackupCreated = 'backup_created';
    case BackupDownloaded = 'backup_downloaded';
    case BackupRestored = 'backup_restored';
    case BackupRestoreFailed = 'backup_restore_failed';

    public function label(): string
    {
        return str($this->value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function color(): string
    {
        return match ($this) {
            self::LoginSucceeded, self::AccountUnlocked, self::AccountReactivated, self::BackupCreated => 'success',
            self::LoginFailed, self::SignatureChallengeFailed, self::AccountLocked, self::BackupRestoreFailed => 'danger',
            self::AccountDeactivated, self::PasswordReset, self::RoleChanged, self::BackupRestored => 'warning',
            default => 'gray',
        };
    }
}
