<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NumberSeriesOverflowBehavior;
use Illuminate\Database\Eloquent\Model;

class NumberSeriesSetting extends Model
{
    protected $fillable = [
        'default_prefix_pattern',
        'default_padding_length',
        'default_suffix',
        'overflow_behavior',
    ];

    protected function casts(): array
    {
        return [
            'default_padding_length' => 'integer',
            'overflow_behavior' => NumberSeriesOverflowBehavior::class,
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'default_prefix_pattern' => '{type}-{department}-',
            'default_padding_length' => 5,
            'default_suffix' => '',
            'overflow_behavior' => NumberSeriesOverflowBehavior::Expand,
        ]);
    }

    public function allowsExpandedPadding(): bool
    {
        return $this->overflow_behavior === NumberSeriesOverflowBehavior::Expand;
    }
}
