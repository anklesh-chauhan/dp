<?php

declare(strict_types=1);

use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Services\AI\Enums\ApprovalNarrativeKind;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;

it('adds create polish and shorten hint actions when ai is enabled', function (): void {
    config()->set('modules.enabled', ['dms', 'ai']);

    $group = ApprovalNarrativeTextarea::decisionRationale();
    $children = $group->getDefaultChildComponents();
    $field = collect($children)->first(
        fn (mixed $component): bool => $component instanceof Textarea,
    );
    $password = collect($children)->first(
        fn (mixed $component): bool => $component instanceof TextInput,
    );

    expect($group)->toBeInstanceOf(Group::class)
        ->and($field)->toBeInstanceOf(Textarea::class)
        ->and($field->getLabel())->toBe('Decision rationale')
        ->and($field->isRequired())->toBeTrue()
        ->and($password)->toBeInstanceOf(TextInput::class)
        ->and($password->getName())->toBe('signature_password');

    $actions = $field->getHintActions();

    expect($actions)->toHaveCount(3)
        ->and(collect($actions)->map(fn (Action $action): string => $action->getLabel())->all())
        ->toBe(['Create', 'Polish', 'Shorten']);
});

it('omits hint actions when ai is disabled', function (): void {
    config()->set('modules.enabled', ['dms']);

    $field = ApprovalNarrativeTextarea::submissionNote();

    expect($field->getLabel())->toBe('Submission note')
        ->and($field->getHintActions())->toBe([]);
});

it('builds a custom submission note field for the shared helper', function (): void {
    config()->set('modules.enabled', ['dms', 'ai']);

    $field = ApprovalNarrativeTextarea::make(
        name: 'reason',
        kind: ApprovalNarrativeKind::SubmissionNote,
        label: 'Custom note',
        helperText: 'Custom helper',
        required: false,
    );

    expect($field->getName())->toBe('reason')
        ->and($field->getLabel())->toBe('Custom note')
        ->and($field->isRequired())->toBeFalse()
        ->and($field->getHintActions())->toHaveCount(3);
});
