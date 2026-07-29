<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentTemplateVariableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplateVariable extends Model
{
    /** @use HasFactory<DocumentTemplateVariableFactory> */
    use HasFactory;

    protected $fillable = [
        'template_version_id',
        'name',
        'label',
        'variable_data_type_id',
        'default_value',
        'validation_rules',
        'options',
        'required',
    ];

    protected function casts(): array
    {
        return [
            'validation_rules' => 'array',
            'options' => 'array',
            'required' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DocumentTemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'template_version_id');
    }

    /**
     * @return BelongsTo<VariableDataType, $this>
     */
    public function variableDataType(): BelongsTo
    {
        return $this->belongsTo(VariableDataType::class);
    }
}
