<?php

declare(strict_types=1);

use App\Models\DocumentTemplateVariable;
use App\Models\VariableDataType;
use App\Support\Sop\VariableTypes\Handlers\RichTextVariableTypeHandler;
use Tests\TestCase;

uses(TestCase::class);

it('accepts tiptap document state and formats it as html', function (): void {
    $variable = new DocumentTemplateVariable([
        'validation_rules' => [],
    ]);
    $variable->setRelation('variableDataType', new VariableDataType(['code' => VariableDataType::RICH_TEXT]));
    $handler = new RichTextVariableTypeHandler;

    expect($handler->validationRules($variable))->not->toContain('string')
        ->and($handler->formatForStorage($variable, [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'abcd']],
            ]],
        ]))->toContain('abcd');
});
