<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes\Handlers;

use App\Models\DocumentTemplateVariable;
use App\Models\VariableDataType;
use App\Support\Formatting\DateFormatSettings;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Carbon\CarbonInterface;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TimePicker;

class DateTimeVariableTypeHandler extends AbstractVariableTypeHandler
{
    public function codes(): array
    {
        return [
            VariableDataType::DATE,
            VariableDataType::DATETIME,
            VariableDataType::TIME,
        ];
    }

    public function makeField(DocumentTemplateVariable $variable, VariableTypeFieldContext $context): Field
    {
        $field = match ($variable->variableDataType?->code) {
            VariableDataType::DATETIME => DateTimePicker::make($context->fieldName),
            VariableDataType::TIME => TimePicker::make($context->fieldName),
            default => DatePicker::make($context->fieldName)->date(),
        };

        return $this->applyCommonConfiguration($field, $variable)
            ->rules($this->validationRules($variable));
    }

    public function parseDefaultValue(?string $defaultValue): mixed
    {
        return $defaultValue;
    }

    public function validationRules(DocumentTemplateVariable $variable): array
    {
        $baseRules = match ($variable->variableDataType?->code) {
            VariableDataType::DATETIME => ['nullable', 'date'],
            VariableDataType::TIME => ['nullable', 'date_format:H:i'],
            default => ['nullable', 'date'],
        };

        return $this->mergeValidationRules($variable, $baseRules);
    }

    public function formatForStorage(DocumentTemplateVariable $variable, mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            return match ($variable->variableDataType?->code) {
                VariableDataType::DATETIME => $value->toDateTimeString(),
                VariableDataType::TIME => $value->format('H:i'),
                default => $value->toDateString(),
            };
        }

        return $this->stringifyScalar($value);
    }

    public function formatForSubstitution(DocumentTemplateVariable $variable, mixed $value): string
    {
        if ($value instanceof CarbonInterface || (is_string($value) && filled($value))) {
            $dates = app(DateFormatSettings::class);

            $formatted = match ($variable->variableDataType?->code) {
                VariableDataType::DATETIME => $dates->formatDateTime($value),
                VariableDataType::TIME => $dates->formatTime($value),
                default => $dates->formatDate($value),
            };

            if (filled($formatted)) {
                return $formatted;
            }
        }

        return $this->formatForStorage($variable, $value);
    }
}
