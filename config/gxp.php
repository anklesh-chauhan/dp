<?php

declare(strict_types=1);

return [
    'password_min_length' => (int) env('GXP_PASSWORD_MIN_LENGTH', 12),
    'password_expiry_days' => (int) env('GXP_PASSWORD_EXPIRY_DAYS', 90),
    'lockout_attempts' => (int) env('GXP_LOCKOUT_ATTEMPTS', 5),
    'lockout_minutes' => (int) env('GXP_LOCKOUT_MINUTES', 30),
    'idle_timeout_minutes' => (int) env('GXP_SESSION_LIFETIME', env('SESSION_LIFETIME', 20)),
    'mfa_required' => filter_var(env('GXP_MFA_REQUIRED', false), FILTER_VALIDATE_BOOL),
    'password_history_count' => (int) env('GXP_PASSWORD_HISTORY_COUNT', 12),
    'test_factory_signature_password' => 'password',
    'backup' => [
        'retain_count' => (int) env('GXP_BACKUP_RETAIN_COUNT', 12),
        'pgsql_bin' => env('GXP_BACKUP_PGSQL_BIN'),
        'disks' => ['local', 'public'],
    ],
];
