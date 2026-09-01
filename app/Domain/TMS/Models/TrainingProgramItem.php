<?php

declare(strict_types=1);

namespace App\Domain\TMS\Models;

use App\Models\ControlledDocument;
use Database\Factories\Domain\TMS\Models\TrainingProgramItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TrainingProgramItem extends Model
{
    /** @use HasFactory<TrainingProgramItemFactory> */
    use HasFactory;

    protected $fillable = [
        'training_program_id',
        'controlled_document_id',
        'is_required',
        'sort_order',
    ];

    protected $attributes = [
        'is_required' => true,
        'sort_order' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<TrainingProgram, $this> */
    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    /** @return BelongsTo<ControlledDocument, $this> */
    public function controlledDocument(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class);
    }
}
