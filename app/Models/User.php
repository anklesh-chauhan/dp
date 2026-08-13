<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'department_id', 'designation_id', 'app_guide_completed_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPanelShield, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'app_guide_completed_at' => 'datetime',
        ];
    }

    public function hasCompletedAppGuide(): bool
    {
        return $this->app_guide_completed_at !== null;
    }

    public function markAppGuideCompleted(): void
    {
        if ($this->hasCompletedAppGuide()) {
            return;
        }

        $this->forceFill(['app_guide_completed_at' => now()])->save();
    }

    public function resetAppGuide(): void
    {
        $this->forceFill(['app_guide_completed_at' => null])->save();
    }

    public function getFilamentAvatarUrl(): string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Designation, $this>
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }
}
