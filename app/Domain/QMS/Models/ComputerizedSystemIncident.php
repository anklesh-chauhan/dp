<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Concerns\ProvidesNarrativeSignatureContent;
use App\Domain\QMS\Enums\ComputerizedSystemIncidentSeverity;
use App\Domain\QMS\Enums\ComputerizedSystemIncidentStatus;
use App\Domain\QMS\Policies\ComputerizedSystemIncidentPolicy;
use App\Domain\Shared\Contracts\ProvidesElectronicSignatureContent;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\ComputerizedSystemIncidentFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[UsePolicy(ComputerizedSystemIncidentPolicy::class)]
final class ComputerizedSystemIncident extends Model implements ProvidesElectronicSignatureContent
{
    /** @use HasFactory<ComputerizedSystemIncidentFactory> */
    use HasFactory;

    use ProvidesNarrativeSignatureContent;

    protected static function newFactory(): ComputerizedSystemIncidentFactory
    {
        return ComputerizedSystemIncidentFactory::new();
    }

    protected $fillable = [
        'incident_number', 'title', 'status', 'severity', 'category', 'description', 'impact',
        'owner_id', 'created_by', 'detected_at', 'resolved_at', 'closed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $incident): void {
            $incident->incident_number ??= sprintf(
                'CSI-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ComputerizedSystemIncidentStatus::class,
            'severity' => ComputerizedSystemIncidentSeverity::class,
            'detected_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<ComputerizedSystemIncidentEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(ComputerizedSystemIncidentEvent::class, 'incident_id');
    }
}
