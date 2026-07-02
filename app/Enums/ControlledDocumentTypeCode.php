<?php

declare(strict_types=1);

namespace App\Enums;

enum ControlledDocumentTypeCode: string
{
    case Sop = 'SOP';
    case Log = 'LOG';
    case BatchRecord = 'BMR';
    case Form = 'FORM';

    public function label(): string
    {
        return match ($this) {
            self::Sop => 'Standard Operating Procedure',
            self::Log => 'Log Document',
            self::BatchRecord => 'Batch Manufacturing Record',
            self::Form => 'Controlled Form',
        };
    }

    public function requiresSopReference(): bool
    {
        return match ($this) {
            self::Log, self::BatchRecord, self::Form => true,
            self::Sop => false,
        };
    }

    /**
     * @return array<int, self>
     */
    public static function issuableTypes(): array
    {
        return [self::Log, self::BatchRecord, self::Form];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
