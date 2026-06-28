<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalDecision;
use Database\Factories\SopApprovalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopApproval extends Model
{
    /** @use HasFactory<SopApprovalFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'workflow_step_id',
        'approved_by',
        'decision',
        'comments',
        'approved_at',
        'signature_hash',
    ];

    protected function casts(): array
    {
        return [
            'decision' => ApprovalDecision::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SopDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(SopDocument::class, 'document_id');
    }

    /**
     * @return BelongsTo<SopWorkflowStep, $this>
     */
    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(SopWorkflowStep::class, 'workflow_step_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
