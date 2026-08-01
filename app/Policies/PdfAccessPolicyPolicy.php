<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PdfAccessPolicy;
use Illuminate\Foundation\Auth\User as AuthUser;

class PdfAccessPolicyPolicy
{
    public function viewAny(AuthUser $user): bool
    {
        return $user->can('ManagePdfPolicies:ControlledDocument');
    }

    public function view(AuthUser $user, PdfAccessPolicy $policy): bool
    {
        return $this->viewAny($user);
    }

    public function create(AuthUser $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(AuthUser $user, PdfAccessPolicy $policy): bool
    {
        return $this->viewAny($user);
    }

    public function delete(AuthUser $user, PdfAccessPolicy $policy): bool
    {
        return $this->viewAny($user);
    }

    public function deleteAny(AuthUser $user): bool
    {
        return $this->viewAny($user);
    }
}
