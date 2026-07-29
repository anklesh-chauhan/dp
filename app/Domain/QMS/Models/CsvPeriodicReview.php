<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Models\User;
use Database\Factories\Domain\QMS\Models\CsvPeriodicReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CsvPeriodicReview extends Model
{
    /** @use HasFactory<CsvPeriodicReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'csv_validation_project_id', 'review_no', 'due_date', 'reviewed_on',
        'review_scope', 'findings', 'validation_conclusion', 'revalidation_required',
        'next_review_date', 'reviewed_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'immutable_date',
            'reviewed_on' => 'immutable_date',
            'review_scope' => 'array',
            'revalidation_required' => 'boolean',
            'next_review_date' => 'immutable_date',
            'approved_at' => 'immutable_datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(CsvValidationProject::class, 'csv_validation_project_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
