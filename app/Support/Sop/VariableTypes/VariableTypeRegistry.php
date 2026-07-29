<?php

declare(strict_types=1);

namespace App\Support\Sop\VariableTypes;

use App\Models\DocumentTemplateVariable;
use App\Models\VariableDataType;
use App\Support\Sop\VariableTypes\Contracts\VariableTypeHandler;
use App\Support\Sop\VariableTypes\Handlers\BooleanVariableTypeHandler;
use App\Support\Sop\VariableTypes\Handlers\ChoiceVariableTypeHandler;
use App\Support\Sop\VariableTypes\Handlers\DateTimeVariableTypeHandler;
use App\Support\Sop\VariableTypes\Handlers\DocumentNumberVariableTypeHandler;
use App\Support\Sop\VariableTypes\Handlers\FileVariableTypeHandler;
use App\Support\Sop\VariableTypes\Handlers\LongTextVariableTypeHandler;
use App\Support\Sop\VariableTypes\Handlers\NumericVariableTypeHandler;
use App\Support\Sop\VariableTypes\Handlers\RelationshipVariableTypeHandler;
use App\Support\Sop\VariableTypes\Handlers\RichTextVariableTypeHandler;
use App\Support\Sop\VariableTypes\Handlers\TextVariableTypeHandler;
use Filament\Forms\Components\Field;
use InvalidArgumentException;

class VariableTypeRegistry
{
    /**
     * @var array<string, VariableTypeHandler>
     */
    private array $handlersByCode = [];

    /**
     * @param  iterable<VariableTypeHandler>  $handlers
     */
    public function __construct(iterable $handlers = [])
    {
        foreach ($handlers as $handler) {
            $this->register($handler);
        }
    }

    public function register(VariableTypeHandler $handler): void
    {
        foreach ($handler->codes() as $code) {
            $this->handlersByCode[$code] = $handler;
        }
    }

    public function forCode(?string $code): VariableTypeHandler
    {
        if ($code === null || $code === '') {
            return $this->handlersByCode[VariableDataType::TEXT]
                ?? throw new InvalidArgumentException('No default text variable type handler is registered.');
        }

        return $this->handlersByCode[$code]
            ?? $this->handlersByCode[VariableDataType::TEXT]
            ?? throw new InvalidArgumentException("No variable type handler is registered for code [{$code}].");
    }

    public function makeField(DocumentTemplateVariable $variable, VariableTypeFieldContext $context): Field
    {
        return $this->forCode($variable->variableDataType?->code)
            ->makeField($variable, $context);
    }

    public function parseDefaultValue(DocumentTemplateVariable $variable): mixed
    {
        return $this->forCode($variable->variableDataType?->code)
            ->parseDefaultValue($variable->default_value);
    }

    /**
     * @return array<int, mixed>
     */
    public function validationRules(DocumentTemplateVariable $variable): array
    {
        return $this->forCode($variable->variableDataType?->code)
            ->validationRules($variable);
    }

    public function formatForStorage(DocumentTemplateVariable $variable, mixed $value): string
    {
        return $this->forCode($variable->variableDataType?->code)
            ->formatForStorage($variable, $value);
    }

    public function formatForSubstitution(DocumentTemplateVariable $variable, mixed $value): string
    {
        return $this->forCode($variable->variableDataType?->code)
            ->formatForSubstitution($variable, $value);
    }

    public static function default(): self
    {
        return new self([
            new TextVariableTypeHandler,
            new LongTextVariableTypeHandler,
            new RichTextVariableTypeHandler,
            new DocumentNumberVariableTypeHandler,
            new NumericVariableTypeHandler,
            new DateTimeVariableTypeHandler,
            new BooleanVariableTypeHandler,
            new ChoiceVariableTypeHandler,
            new RelationshipVariableTypeHandler,
            new FileVariableTypeHandler,
        ]);
    }
}
