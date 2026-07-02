<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait Lockable
{
    public function isLocked(): bool
    {
        return $this->locked_by !== null && $this->locked_at !== null;
    }

    public function isLockedBy(User $user): bool
    {
        return $this->isLocked() && $this->locked_by === $user->id;
    }

    public function isLockedByOther(User $user): bool
    {
        return $this->isLocked() && $this->locked_by !== $user->id;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
