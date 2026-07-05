<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Handlers;

use App\Models\SopTemplateVariable;
use App\Models\VariableDataType;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;

class FileVariableTypeHandler extends AbstractVariableTypeHandler
{
    public function codes(): array
    {
        return [
            VariableDataType::FILE,
            VariableDataType::IMAGE,
        ];
    }

    public function makeField(SopTemplateVariable $variable, VariableTypeFieldContext $context): Field
    {
        $field = FileUpload::make($context->fieldName)
            ->directory('sop-variables')
            ->visibility('private');

        if ($variable->variableDataType?->hasCode(VariableDataType::IMAGE)) {
            $field = $field->image();
        }

        return $this->applyCommonConfiguration($field, $variable)
            ->rules($this->validationRules($variable));
    }

    public function parseDefaultValue(?string $defaultValue): mixed
    {
        return $defaultValue;
    }

    public function validationRules(SopTemplateVariable $variable): array
    {
        $baseRules = $variable->variableDataType?->hasCode(VariableDataType::IMAGE)
            ? ['nullable', 'string']
            : ['nullable', 'string'];

        return $this->mergeValidationRules($variable, $baseRules);
    }

    public function formatForStorage(SopTemplateVariable $variable, mixed $value): string
    {
        if (is_array($value)) {
            return (string) (array_values($value)[0] ?? '');
        }

        return $this->stringifyScalar($value);
    }

    public function formatForSubstitution(SopTemplateVariable $variable, mixed $value): string
    {
        $path = $this->formatForStorage($variable, $value);

        if ($path === '') {
            return '';
        }

        if ($variable->variableDataType?->hasCode(VariableDataType::IMAGE)) {
            $url = Storage::disk(config('filament.default_filesystem_disk'))->url($path);

            return '<img src="'.e($url).'" alt="'.e($variable->label).'" />';
        }

        return e(basename($path));
    }
}
