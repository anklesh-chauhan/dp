<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Policies\ChangeControlPolicy;
use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Models\Department;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\ChangeControlFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[UsePolicy(ChangeControlPolicy::class)]
final class ChangeControl extends Model implements ApprovableSubject
{
    /** @use HasFactory<ChangeControlFactory> */
    use HasFactory;

    protected $fillable = [
        'change_number',
        'title',
        'description',
        'rationale',
        'status',
        'department_id',
        'requested_by',
        'owner_id',
        'submitted_at',
        'approved_at',
        'implementation_due_at',
        'implemented_at',
        'effectiveness_due_at',
        'effectiveness_verified_at',
        'closed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $changeControl): void {
            $changeControl->change_number ??= sprintf(
                'CC-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ChangeControlStatus::class,
            'submitted_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'implementation_due_at' => 'immutable_date',
            'implemented_at' => 'immutable_datetime',
            'effectiveness_due_at' => 'immutable_date',
            'effectiveness_verified_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<ChangeControlDocumentImpact, $this> */
    public function documentImpacts(): HasMany
    {
        return $this->hasMany(ChangeControlDocumentImpact::class);
    }

    /** @return HasMany<ChangeControlAuditEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(ChangeControlAuditEvent::class);
    }

    public function approvalSubjectKey(): int|string|null
    {
        $key = $this->getKey();

        return is_int($key) || is_string($key) ? $key : null;
    }

    public function approvalSubjectReference(): string
    {
        return (string) $this->change_number;
    }

    public function approvalSubjectTitle(): string
    {
        return (string) $this->title;
    }

    public function approvalSubjectDepartmentId(): ?int
    {
        return $this->department_id === null ? null : (int) $this->department_id;
    }

    public function approvalSubjectCreatedById(): ?int
    {
        return $this->requested_by === null ? null : (int) $this->requested_by;
    }

    public function approvalSubjectOwnerId(): ?int
    {
        return $this->owner_id === null ? null : (int) $this->owner_id;
    }
}
