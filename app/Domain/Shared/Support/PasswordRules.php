<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

use Illuminate\Validation\Rules\Password;

final class PasswordRules
{
    public static function required(): Password
    {
        return Password::min((int) config('gxp.password_min_length', 12))
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols();
    }
}
