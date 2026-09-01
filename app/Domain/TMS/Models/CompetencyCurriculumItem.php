<?php

declare(strict_types=1);

namespace App\Domain\TMS\Models;

use App\Models\ControlledDocument;
use Database\Factories\Domain\TMS\Models\CompetencyCurriculumItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetencyCurriculumItem extends Model
{
    /** @use HasFactory<CompetencyCurriculumItemFactory> */
    use HasFactory;

    protected $fillable = [
        'curriculum_id',
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

    /** @return BelongsTo<CompetencyCurriculum, $this> */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(CompetencyCurriculum::class, 'curriculum_id');
    }

    /** @return BelongsTo<ControlledDocument, $this> */
    public function controlledDocument(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'controlled_document_id');
    }
}
