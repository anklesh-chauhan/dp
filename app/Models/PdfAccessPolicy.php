<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

class PdfAccessPolicy extends Model
{
    use HasFactory;

    public const EFFECT_ALLOW = 'allow';

    public const EFFECT_DENY = 'deny';

    protected $fillable = [
        'name', 'description', 'effect', 'priority', 'can_view', 'can_print',
        'can_download', 'department_id', 'document_type_id', 'document_status_id',
        'effective_from', 'expires_at', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_print' => 'boolean',
            'can_download' => 'boolean',
            'is_active' => 'boolean',
            'effective_from' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $query): Builder => $query->whereNull('effective_from')->orWhere('effective_from', '<=', now()))
            ->where(fn (Builder $query): Builder => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'pdf_access_policy_role');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function documentStatus(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
