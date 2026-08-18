<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Shared\Enums\SecurityAuditEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SecurityAuditEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_uuid',
        'event_type',
        'actor_id',
        'actor_name',
        'actor_email',
        'subject_user_id',
        'subject_email',
        'reason',
        'context',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('Security audit events are append-only.'));
        self::deleting(fn () => throw new LogicException('Security audit events are append-only.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => SecurityAuditEventType::class,
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function subjectUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }
}
