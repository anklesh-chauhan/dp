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
}
