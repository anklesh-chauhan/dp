<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SopTemplateVariableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopTemplateVariable extends Model
{
    /** @use HasFactory<SopTemplateVariableFactory> */
    use HasFactory;

    protected $fillable = [
        'template_version_id',
        'name',
        'label',
        'variable_data_type_id',
        'default_value',
        'validation_rules',
        'required',
    ];

    protected function casts(): array
    {
        return [
            'validation_rules' => 'array',
            'required' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SopTemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(SopTemplateVersion::class, 'template_version_id');
    }

    /**
     * @return BelongsTo<VariableDataType, $this>
     */
    public function variableDataType(): BelongsTo
    {
        return $this->belongsTo(VariableDataType::class);
    }
}
