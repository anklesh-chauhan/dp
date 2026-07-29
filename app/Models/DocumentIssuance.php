<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentIssuanceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentIssuance extends Model
{
    /** @use HasFactory<DocumentIssuanceFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'copy_number',
        'issuance_number',
        'issued_to_user_id',
        'issued_to_department_id',
        'issued_to_location',
        'issued_by',
        'issued_at',
        'issuance_status_id',
        'recalled_by',
        'recalled_at',
        'recall_reason',
        'destroyed_by',
        'destroyed_at',
        'destroy_reason',
        'watermark_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'copy_number' => 'integer',
            'issued_at' => 'datetime',
            'recalled_at' => 'datetime',
            'destroyed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ControlledDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'document_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_to_user_id');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function issuedToDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'issued_to_department_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recaller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recalled_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function destroyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destroyed_by');
    }

    /**
     * @return BelongsTo<IssuanceStatus, $this>
     */
    public function issuanceStatus(): BelongsTo
    {
        return $this->belongsTo(IssuanceStatus::class);
    }

    public function isActive(): bool
    {
        return $this->issuanceStatus?->hasCode(IssuanceStatus::ACTIVE) ?? false;
    }

    /**
     * @param  Builder<DocumentIssuance>  $query
     * @return Builder<DocumentIssuance>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('issuanceStatus', fn (Builder $statusQuery): Builder => $statusQuery->where('code', IssuanceStatus::ACTIVE));
    }
}
