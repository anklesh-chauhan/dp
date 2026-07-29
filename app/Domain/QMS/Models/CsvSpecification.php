<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Enums\CsvSpecificationType;
use App\Models\ControlledDocument;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\CsvSpecificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CsvSpecification extends Model
{
    /** @use HasFactory<CsvSpecificationFactory> */
    use HasFactory;

    protected $fillable = [
        'csv_validation_project_id', 'specification_identifier', 'version', 'type',
        'title', 'description', 'status', 'controlled_document_id', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => CsvSpecificationType::class,
            'status' => CsvRequirementStatus::class,
            'approved_at' => 'immutable_datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(CsvValidationProject::class, 'csv_validation_project_id');
    }

    public function controlledDocument(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
