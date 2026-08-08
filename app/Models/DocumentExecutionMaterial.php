<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentExecutionMaterialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class DocumentExecutionMaterial extends Model
{
    /** @use HasFactory<DocumentExecutionMaterialFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $material): void {
            if ($material->exists && ! $material->execution()->firstOrFail()->isEditable()) {
                throw new LogicException('A completed or reviewed execution record cannot be changed.');
            }
        });
    }

    protected $fillable = [
        'document_execution_id', 'material_order', 'material_name', 'material_code',
        'lot_number', 'planned_quantity', 'actual_quantity', 'unit', 'status',
        'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'material_order' => 'integer',
            'planned_quantity' => 'decimal:8',
            'actual_quantity' => 'decimal:8',
            'verified_at' => 'datetime',
        ];
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(DocumentExecution::class, 'document_execution_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isReconciled(): bool
    {
        return $this->planned_quantity !== null
            && $this->actual_quantity !== null
            && (float) $this->planned_quantity === (float) $this->actual_quantity;
    }
}
