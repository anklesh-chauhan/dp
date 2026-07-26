<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\DocumentImpactAction;
use App\Models\SopDocument;
use Database\Factories\Domain\QMS\Models\ChangeControlDocumentImpactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ChangeControlDocumentImpact extends Model
{
    /** @use HasFactory<ChangeControlDocumentImpactFactory> */
    use HasFactory;

    protected $fillable = [
        'change_control_id',
        'source_document_id',
        'result_document_id',
        'required_action',
        'rationale',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'required_action' => DocumentImpactAction::class,
        ];
    }

    /** @return BelongsTo<ChangeControl, $this> */
    public function changeControl(): BelongsTo
    {
        return $this->belongsTo(ChangeControl::class);
    }

    /** @return BelongsTo<SopDocument, $this> */
    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(SopDocument::class, 'source_document_id');
    }

    /** @return BelongsTo<SopDocument, $this> */
    public function resultDocument(): BelongsTo
    {
        return $this->belongsTo(SopDocument::class, 'result_document_id');
    }
}
