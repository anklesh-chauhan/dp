<?php

namespace App\Models;

use Database\Factories\ControlledDocumentPdfFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledDocumentPdf extends Model
{
    /** @use HasFactory<ControlledDocumentPdfFactory> */
    use HasFactory;

    protected $fillable = [
        'controlled_document_id',
        'report_template_id',
        'document_issuance_id',
        'artifact_key',
        'document_version',
        'template_layout_key',
        'disk',
        'path',
        'filename',
        'mime_type',
        'size_bytes',
        'sha256',
        'renderer',
        'renderer_version',
        'generated_by',
        'generated_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'controlled_document_id');
    }

    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class);
    }

    public function issuance(): BelongsTo
    {
        return $this->belongsTo(DocumentIssuance::class, 'document_issuance_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
