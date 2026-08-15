<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ControlledDocumentSectionReviewCommentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledDocumentSectionReviewComment extends Model
{
    /** @use HasFactory<ControlledDocumentSectionReviewCommentFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'section_id',
        'sop_approval_id',
        'author_id',
        'body',
        'resolved_at',
        'resolved_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $comment): void {
            if ($comment->document_id !== null || $comment->section_id === null) {
                return;
            }

            $comment->document_id = ControlledDocumentSection::query()
                ->whereKey($comment->section_id)
                ->value('document_id');
        });
    }

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function isOpen(): bool
    {
        return $this->resolved_at === null;
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function attentionMarkdown(): string
    {
        $author = $this->author?->name ?? 'Reviewer';
        $status = $this->isOpen() ? 'Needs update' : 'Addressed';
        $when = $this->created_at?->timezone((string) config('app.timezone'))->toDayDateTimeString();

        return '**'.$author.'** · '.$status.(filled($when) ? ' · '.$when : '')."\n\n".$this->body;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereNotNull('resolved_at');
    }

    /** @return BelongsTo<ControlledDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'document_id');
    }

    /** @return BelongsTo<ControlledDocumentSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ControlledDocumentSection::class, 'section_id');
    }

    /** @return BelongsTo<SopApproval, $this> */
    public function approval(): BelongsTo
    {
        return $this->belongsTo(SopApproval::class, 'sop_approval_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<User, $this> */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
