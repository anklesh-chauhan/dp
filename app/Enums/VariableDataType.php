<?php

declare(strict_types=1);

namespace App\Enums;

enum VariableDataType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Date = 'date';
    case Select = 'select';
    case Boolean = 'boolean';
    case User = 'user';
    case Department = 'department';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Textarea => 'Textarea',
            self::Number => 'Number',
            self::Date => 'Date',
            self::Select => 'Select',
            self::Boolean => 'Boolean',
            self::User => 'User',
            self::Department => 'Department',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $datatype): array => [$datatype->value => $datatype->label()])
            ->all();
    }
}
