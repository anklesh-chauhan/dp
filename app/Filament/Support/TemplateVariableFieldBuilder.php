<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Enums\ControlledDocumentTypeCode;
use App\Enums\DocumentStatus;
use App\Enums\VariableDataType;
use App\Models\Department;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\SopTemplateVariable;
use App\Models\SopTemplateVersion;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Builder;

class TemplateVariableFieldBuilder
{
    /**
     * Variables populated automatically during document generation.
     *
     * @var list<string>
     */
    private const AUTO_POPULATED_NAMES = [
        'document_number',
    ];

    /**
     * @param  list<string>  $additionalExcludedNames
     * @return array<int, Field>
     */
    public static function fields(?int $templateVersionId, ?int $templateId = null, array $additionalExcludedNames = []): array
    {
        if ($templateVersionId === null || $templateVersionId === 0) {
            return [];
        }

        return SopTemplateVersion::query()
            ->with('variables')
            ->find($templateVersionId)
            ?->variables
            ->reject(fn (SopTemplateVariable $variable): bool => self::shouldExcludeFromForm($variable, $additionalExcludedNames))
            ->map(fn (SopTemplateVariable $variable): Field => self::field($variable, $templateId))
            ->values()
            ->all() ?? [];
    }

    /**
     * @param  list<string>  $additionalExcludedNames
     * @return array<string, mixed>
     */
    public static function defaultValues(?int $templateVersionId, array $additionalExcludedNames = []): array
    {
        if ($templateVersionId === null) {
            return [];
        }

        return SopTemplateVersion::query()
            ->with('variables')
            ->find($templateVersionId)
            ?->variables
            ->reject(fn (SopTemplateVariable $variable): bool => self::shouldExcludeFromForm($variable, $additionalExcludedNames))
            ->mapWithKeys(fn (SopTemplateVariable $variable): array => [$variable->name => match ($variable->datatype) {
                VariableDataType::Boolean => filter_var($variable->default_value, FILTER_VALIDATE_BOOLEAN),
                default => $variable->default_value,
            }])
            ->all() ?? [];
    }

    /**
     * @param  list<string>  $additionalExcludedNames
     */
    public static function shouldExcludeFromForm(SopTemplateVariable $variable, array $additionalExcludedNames = []): bool
    {
        if (in_array($variable->name, $additionalExcludedNames, true)) {
            return true;
        }

        if (in_array($variable->name, self::AUTO_POPULATED_NAMES, true)) {
            return true;
        }

        return match ($variable->name) {
            'department' => $variable->datatype !== VariableDataType::Department,
            'referenced_sop' => $variable->datatype !== VariableDataType::SopReference,
            default => false,
        };
    }

    private static function field(SopTemplateVariable $variable, ?int $templateId): Field
    {
        $field = match ($variable->datatype) {
            VariableDataType::Textarea => RichEditor::make("variables.{$variable->name}")->columnSpanFull(),
            VariableDataType::Date => DatePicker::make("variables.{$variable->name}")->date(),
            VariableDataType::Number => TextInput::make("variables.{$variable->name}")->numeric(),
            VariableDataType::Boolean => Toggle::make("variables.{$variable->name}"),
            VariableDataType::Select => Select::make("variables.{$variable->name}")
                ->options(self::selectVariableOptions($variable))
                ->searchable(),
            VariableDataType::User => Select::make("variables.{$variable->name}")
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->preload(),
            VariableDataType::Department => Select::make("variables.{$variable->name}")
                ->options(fn (): array => Department::query()->orderBy('name')->pluck('name', 'name')->all())
                ->searchable()
                ->preload(),
            VariableDataType::SopReference => Select::make("variables.{$variable->name}")
                ->options(fn (): array => self::effectiveSopOptions($templateId))
                ->searchable()
                ->preload(),
            default => TextInput::make("variables.{$variable->name}"),
        };

        return $field
            ->label($variable->label)
            ->required($variable->required)
            ->rules(self::validationRules($variable));
    }

    /**
     * @return array<string, string>
     */
    public static function effectiveSopOptions(?int $templateId): array
    {
        if ($templateId === null || $templateId === 0) {
            return [];
        }

        $departmentId = SopTemplate::query()->whereKey($templateId)->value('department_id');

        if ($departmentId === null) {
            return [];
        }

        return SopDocument::query()
            ->where('department_id', $departmentId)
            ->where('status', DocumentStatus::Effective)
            ->whereHas('documentType', fn (Builder $query): Builder => $query->where('code', ControlledDocumentTypeCode::Sop->value))
            ->orderBy('document_number')
            ->pluck('document_number', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function selectVariableOptions(SopTemplateVariable $variable): array
    {
        foreach (self::validationRules($variable) as $rule) {
            if (! is_string($rule) || ! str_starts_with($rule, 'in:')) {
                continue;
            }

            return collect(explode(',', str($rule)->after('in:')->toString()))
                ->mapWithKeys(fn (string $option): array => [trim($option) => str(trim($option))->replace('_', ' ')->title()->toString()])
                ->all();
        }

        return [];
    }

    /**
     * @return array<int, mixed>
     */
    private static function validationRules(SopTemplateVariable $variable): array
    {
        if (! is_array($variable->validation_rules)) {
            return [];
        }

        return collect($variable->validation_rules)
            ->map(function (mixed $value, int|string $key): mixed {
                if (is_int($key)) {
                    return $value;
                }

                return filled($value) ? "{$key}:{$value}" : $key;
            })
            ->values()
            ->all();
    }
}
