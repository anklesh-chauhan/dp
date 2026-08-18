<?php

namespace App\Models;

use App\Domain\Shared\Services\UserAccessService;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'department_id', 'designation_id', 'app_guide_completed_at'])]
#[Hidden(['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPanelShield, HasRoles, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->identity_uuid ??= (string) Str::uuid();
            $user->password_changed_at ??= now();
        });

        static::updating(function (User $user): void {
            if ($user->isDirty('password') && ! $user->isDirty('must_change_password')) {
                $user->must_change_password = false;
                $user->password_changed_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'app_guide_completed_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'must_change_password' => 'boolean',
            'failed_login_attempts' => 'integer',
            'locked_at' => 'datetime',
            'last_login_at' => 'datetime',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isDeactivated() || $this->isLocked()) {
            return false;
        }

        if ($this->hasRole(Utils::getSuperAdminName())) {
            return true;
        }

        return (bool) $this->hasRole(Utils::getPanelUserRoleName());
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function mustChangePassword(): bool
    {
        return (bool) $this->must_change_password
            || app(UserAccessService::class)->passwordHasExpired($this);
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->forceFill(['app_authentication_secret' => $secret])->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    /**
     * @return ?array<string>
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        $codes = $this->app_authentication_recovery_codes;

        return is_array($codes) ? $codes : null;
    }

    /**
     * @param  ?array<string>  $codes
     */
    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->forceFill(['app_authentication_recovery_codes' => $codes])->save();
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
