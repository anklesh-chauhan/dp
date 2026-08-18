<?php

declare(strict_types=1);

return [
    'password_min_length' => (int) env('GXP_PASSWORD_MIN_LENGTH', 12),
    'password_expiry_days' => (int) env('GXP_PASSWORD_EXPIRY_DAYS', 90),
    'lockout_attempts' => (int) env('GXP_LOCKOUT_ATTEMPTS', 5),
    'lockout_minutes' => (int) env('GXP_LOCKOUT_MINUTES', 30),
    'idle_timeout_minutes' => (int) env('GXP_SESSION_LIFETIME', env('SESSION_LIFETIME', 20)),
    'mfa_required' => filter_var(env('GXP_MFA_REQUIRED', false), FILTER_VALIDATE_BOOL),
    'test_factory_signature_password' => 'password',
];
