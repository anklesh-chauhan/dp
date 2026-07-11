<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\KnowledgeGuideFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KnowledgeGuide extends Model
{
    /** @use HasFactory<KnowledgeGuideFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'sort_order',
        'is_published',
    ];

    protected static function booted(): void
    {
        static::saving(function (KnowledgeGuide $guide): void {
            if (blank($guide->slug) && filled($guide->title)) {
                $guide->slug = Str::slug($guide->title);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    public function renderedHtml(): string
    {
        return Str::markdown($this->content);
    }
}
