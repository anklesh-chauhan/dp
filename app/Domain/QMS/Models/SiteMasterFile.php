<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\SiteMasterFileStatus;
use App\Domain\QMS\Policies\SiteMasterFilePolicy;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\SiteMasterFileFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

#[UsePolicy(SiteMasterFilePolicy::class)]
final class SiteMasterFile extends Model
{
    /** @use HasFactory<SiteMasterFileFactory> */
    use HasFactory;

    protected $fillable = [
        'smf_number',
        'title',
        'status',
        'version',
        'sections',
        'owner_id',
        'created_by',
        'published_by',
        'published_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $file): void {
            $file->smf_number ??= sprintf(
                'SMF-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
            $file->version ??= 1;
            $file->sections ??= self::defaultSections();
        });
    }

    /**
     * @return array<string, array{content: string|null, document_id: int|null}>
     */
    public static function defaultSections(): array
    {
        return [
            'site_information' => ['content' => null, 'document_id' => null],
            'pharmaceutical_quality_system' => ['content' => null, 'document_id' => null],
            'personnel' => ['content' => null, 'document_id' => null],
            'premises_and_equipment' => ['content' => null, 'document_id' => null],
            'documentation' => ['content' => null, 'document_id' => null],
            'production' => ['content' => null, 'document_id' => null],
            'quality_control' => ['content' => null, 'document_id' => null],
            'distribution_complaints_recalls' => ['content' => null, 'document_id' => null],
            'self_inspection' => ['content' => null, 'document_id' => null],
            'contract_activities' => ['content' => null, 'document_id' => null],
        ];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => SiteMasterFileStatus::class,
            'version' => 'integer',
            'sections' => 'array',
            'published_at' => 'immutable_datetime',
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

    /** @return BelongsTo<User, $this> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /** @return HasMany<SiteMasterFileEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(SiteMasterFileEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
