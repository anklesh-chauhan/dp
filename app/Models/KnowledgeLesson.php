<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\KnowledgeLessonPolicy;
use Database\Factories\KnowledgeLessonFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

#[UsePolicy(KnowledgeLessonPolicy::class)]
class KnowledgeLesson extends Model
{
    /** @use HasFactory<KnowledgeLessonFactory> */
    use HasFactory;

    protected $fillable = [
        'lesson_number', 'title', 'summary', 'body', 'source_type', 'source_id', 'created_by', 'is_published',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $lesson): void {
            $lesson->lesson_number ??= sprintf(
                'KL-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
