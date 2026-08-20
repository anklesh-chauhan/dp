<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Concerns\StoresSignatureContentDigest;
use App\Domain\Shared\Contracts\ElectronicSignatureRecord;
use App\Domain\Shared\Contracts\HasSignatureContentDigest;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\CsvSignedDecisionFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

final class CsvSignedDecision extends Model implements ElectronicSignatureRecord, HasSignatureContentDigest
{
    /** @use HasFactory<CsvSignedDecisionFactory> */
    use HasFactory;

    use StoresSignatureContentDigest;

    public $timestamps = false;

    protected $fillable = [
        'decision_uuid',
        'csv_validation_project_id',
        'subject_type',
        'subject_id',
        'decision_code',
        'from_state',
        'to_state',
        'actor_id',
        'reason',
        'context',
        'signature_hash',
        'signature_ip_address',
        'signature_user_agent',
        'occurred_at',
    ];

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('CSV signed decisions are append-only.'));
        self::deleting(fn () => throw new LogicException('CSV signed decisions are append-only.'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<CsvValidationProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(CsvValidationProject::class, 'csv_validation_project_id');
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function signatureRecordKey(): int|string|null
    {
        return $this->decision_uuid;
    }

    public function signatureMeaning(): ?string
    {
        return $this->decision_code;
    }

    public function signatureSignerId(): int|string|null
    {
        return $this->actor_id;
    }

    public function signatureTimestamp(): ?DateTimeInterface
    {
        return $this->occurred_at;
    }

    public function signatureHash(): ?string
    {
        return $this->signature_hash;
    }

    public function signatureReason(): ?string
    {
        return $this->reason;
    }

    public function signatureIpAddress(): ?string
    {
        return $this->signature_ip_address;
    }

    public function signatureUserAgent(): ?string
    {
        return $this->signature_user_agent;
    }
}
