<?php

declare(strict_types=1);

use App\Filament\Support\ContentAiAssist;
use App\Models\DocumentTemplateVariable;
use App\Models\VariableDataType;
use App\Services\AI\Enums\ContentAssistFormat;
use App\Support\Sop\VariableTypes\Handlers\LongTextVariableTypeHandler;
use App\Support\Sop\VariableTypes\Handlers\RichTextVariableTypeHandler;
use App\Support\Sop\VariableTypes\VariableTypeFieldContext;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;

it('attaches create polish and shorten hint actions when ai is enabled', function (): void {
    config()->set('modules.enabled', ['dms', 'ai']);

    $field = ContentAiAssist::attach(
        field: Textarea::make('purpose'),
        format: ContentAssistFormat::Plain,
    );

    $actions = $field->getHintActions();

    expect($actions)->toHaveCount(3)
        ->and(collect($actions)->map(fn (Action $action): string => $action->getLabel())->all())
        ->toBe(['Create', 'Polish', 'Shorten']);
});

it('omits hint actions when ai is disabled', function (): void {
    config()->set('modules.enabled', ['dms']);

    $field = ContentAiAssist::attach(
        field: RichEditor::make('content'),
        format: ContentAssistFormat::Html,
    );

    expect($field->getHintActions())->toBe([]);
});

it('adds ai assist actions to rich text and textarea variable fields', function (): void {
    config()->set('modules.enabled', ['dms', 'ai']);

    $richTextVariable = new DocumentTemplateVariable([
        'name' => 'purpose',
        'label' => 'Purpose',
        'required' => false,
        'validation_rules' => [],
    ]);
    $richTextVariable->setRelation('variableDataType', new VariableDataType(['code' => VariableDataType::RICH_TEXT]));

    $longTextVariable = new DocumentTemplateVariable([
        'name' => 'notes',
        'label' => 'Notes',
        'required' => false,
        'validation_rules' => [],
    ]);
    $longTextVariable->setRelation('variableDataType', new VariableDataType(['code' => VariableDataType::LONG_TEXT]));

    $richField = (new RichTextVariableTypeHandler)->makeField(
        $richTextVariable,
        VariableTypeFieldContext::forDocumentCreation($richTextVariable, 1),
    );

    $longField = (new LongTextVariableTypeHandler)->makeField(
        $longTextVariable,
        VariableTypeFieldContext::forDocumentCreation($longTextVariable, 1),
    );

    expect($richField)->toBeInstanceOf(RichEditor::class)
        ->and($richField->getHintActions())->toHaveCount(3)
        ->and($longField)->toBeInstanceOf(Textarea::class)
        ->and($longField->getHintActions())->toHaveCount(3);
});
