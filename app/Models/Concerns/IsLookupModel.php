<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * @method static Builder byCode(string $code)
 */
trait IsLookupModel
{
    public function hasCode(string $code): bool
    {
        return $this->code === $code;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    public static function findByCode(string $code): ?static
    {
        return static::query()
            ->byCode($code)
            ->first();
    }

    public static function firstByCodeOrFail(string $code): static
    {
        return static::query()
            ->byCode($code)
            ->firstOrFail();
    }

    public static function idFor(string $code): int
    {
        return Cache::rememberForever(
            static::class.':id:'.$code,
            function () use ($code): int {
                $id = static::query()
                    ->byCode($code)
                    ->value('id');

                if ($id === null) {
                    throw new InvalidArgumentException(
                        "Unknown lookup code [{$code}] for ".static::class.'.'
                    );
                }

                return (int) $id;
            }
        );
    }

    /**
     * @return array<int, string>
     */
    public static function selectOptions(): array
    {
        return Cache::rememberForever(
            static::class.':options',
            fn (): array => static::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all()
        );
    }

    public static function clearLookupCache(): void
    {
        Cache::forget(static::class.':options');

        static::query()
            ->pluck('code')
            ->each(fn (string $code) => Cache::forget(static::class.':id:'.$code));
    }

    protected static function bootIsLookupModel(): void
    {
        static::saved(fn () => static::clearLookupCache());
        static::deleted(fn () => static::clearLookupCache());
    }
}
