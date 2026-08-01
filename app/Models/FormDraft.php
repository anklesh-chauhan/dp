<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FormDraft extends Model
{
    protected $fillable = ['user_id', 'form_key', 'payload', 'last_saved_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'last_saved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
