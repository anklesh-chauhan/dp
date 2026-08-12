<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Enums\ProductModule;
use App\Services\AI\Contracts\ApprovalNarrativeGenerator;
use App\Services\AI\Enums\ApprovalNarrativeKind;
use App\Services\AI\Enums\ApprovalNarrativeOperation;
use App\Support\Modules\ModuleManager;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

final class ApprovalNarrativeTextarea
{
    /**
     * @param  Closure|array<string, mixed>|null  $context
     */
    public static function make(
        string $name,
        ApprovalNarrativeKind $kind,
        ?string $label = null,
        ?string $helperText = null,
        bool $required = true,
        int $rows = 4,
        int $maxLength = 2_000,
        Closure|array|null $context = null,
    ): Textarea {
        $field = Textarea::make($name)
            ->label($label ?? match ($kind) {
                ApprovalNarrativeKind::SubmissionNote => 'Submission note',
                ApprovalNarrativeKind::DecisionRationale => 'Decision rationale',
            })
            ->helperText($helperText ?? $kind->purpose())
            ->rows($rows)
            ->maxLength($maxLength);

        if ($required) {
            $field->required();
        }

        if (! app(ModuleManager::class)->enabled(ProductModule::AI)) {
            return $field;
        }

        return $field->hintActions([
            self::hintAction(
                name: "create{$name}WithAi",
                label: 'Create',
                icon: 'heroicon-m-sparkles',
                fieldName: $name,
                kind: $kind,
                operation: ApprovalNarrativeOperation::Create,
                context: $context,
            ),
            self::hintAction(
                name: "polish{$name}WithAi",
                label: 'Polish',
                icon: 'heroicon-m-document-text',
                fieldName: $name,
                kind: $kind,
                operation: ApprovalNarrativeOperation::Polish,
                context: $context,
            ),
            self::hintAction(
                name: "shorten{$name}WithAi",
                label: 'Shorten',
                icon: 'heroicon-m-scissors',
                fieldName: $name,
                kind: $kind,
                operation: ApprovalNarrativeOperation::Shorten,
                context: $context,
            ),
        ]);
    }

    public static function submissionNote(
        string $name = 'reason',
        ?string $label = null,
        ?string $helperText = null,
        Closure|array|null $context = null,
    ): Textarea {
        return self::make(
            name: $name,
            kind: ApprovalNarrativeKind::SubmissionNote,
            label: $label,
            helperText: $helperText,
            context: $context,
        );
    }

    public static function decisionRationale(
        string $name = 'comments',
        ?string $label = null,
        ?string $helperText = null,
        bool $required = true,
        Closure|array|null $context = null,
    ): Textarea {
        return self::make(
            name: $name,
            kind: ApprovalNarrativeKind::DecisionRationale,
            label: $label,
            helperText: $helperText,
            required: $required,
            context: $context,
        );
    }

    /**
     * @param  Closure|array<string, mixed>|null  $context
     */
    private static function hintAction(
        string $name,
        string $label,
        string $icon,
        string $fieldName,
        ApprovalNarrativeKind $kind,
        ApprovalNarrativeOperation $operation,
        Closure|array|null $context,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->action(function (Get $get, Set $set) use (
                $fieldName,
                $kind,
                $operation,
                $context,
            ): void {
                if (! app(ModuleManager::class)->enabled(ProductModule::AI)) {
                    Notification::make()
                        ->danger()
                        ->title('AI module is not enabled')
                        ->send();

                    return;
                }

                $content = trim((string) ($get($fieldName) ?? ''));

                if (
                    $operation !== ApprovalNarrativeOperation::Create
                    && $content === ''
                ) {
                    Notification::make()
                        ->warning()
                        ->title('Add some text first')
                        ->body("AI can polish or shorten an existing {$kind->label()}.")
                        ->send();

                    return;
                }

                $resolvedContext = [];

                if ($context instanceof Closure) {
                    $resolvedContext = $context($get);
                } elseif (is_array($context)) {
                    $resolvedContext = $context;
                }

                if (! is_array($resolvedContext)) {
                    $resolvedContext = [];
                }

                $result = app(ApprovalNarrativeGenerator::class)->transform(
                    kind: $kind,
                    operation: $operation,
                    content: $content,
                    context: $resolvedContext,
                );

                if ($result === null) {
                    Notification::make()
                        ->danger()
                        ->title('AI assistance failed')
                        ->body("Unable to {$operation->value} the {$kind->label()}. Try again or edit the text manually.")
                        ->send();

                    return;
                }

                $set($fieldName, $result);

                Notification::make()
                    ->success()
                    ->title('AI text updated')
                    ->send();
            });
    }
}
