<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NumberSeriesSetting;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class NumberSeriesSettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('View:ManageNumberSeriesSettings');
    }

    public function view(AuthUser $authUser, NumberSeriesSetting $numberSeriesSetting): bool
    {
        return $authUser->can('View:ManageNumberSeriesSettings');
    }

    public function update(AuthUser $authUser, NumberSeriesSetting $numberSeriesSetting): bool
    {
        return $authUser->can('View:ManageNumberSeriesSettings');
    }
}
