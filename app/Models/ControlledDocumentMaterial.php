<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledDocumentMaterial extends Model
{
    protected $fillable = ['document_id', 'material_order', 'material_name', 'material_code', 'lot_number', 'planned_quantity', 'actual_quantity', 'unit', 'status', 'verified_by', 'verified_at'];

    protected function casts(): array
    {
        return ['material_order' => 'integer', 'planned_quantity' => 'decimal:8', 'actual_quantity' => 'decimal:8', 'verified_at' => 'datetime'];
    }

    /** @return BelongsTo<ControlledDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isReconciled(): bool
    {
        return $this->planned_quantity !== null && $this->actual_quantity !== null && (float) $this->planned_quantity === (float) $this->actual_quantity;
    }
}
