<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Enums\ProductModule;
use App\Services\AI\Contracts\DocumentContentGenerator;
use App\Services\AI\Enums\ContentAssistFormat;
use App\Services\AI\Enums\ContentAssistOperation;
use App\Support\Modules\ModuleManager;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

final class ContentAiAssist
{
    /**
     * @param  Closure|array<string, mixed>|null  $context
     */
    public static function attach(
        Field $field,
        ContentAssistFormat $format = ContentAssistFormat::Plain,
        Closure|array|null $context = null,
    ): Field {
        if (! app(ModuleManager::class)->enabled(ProductModule::AI)) {
            return $field;
        }

        $fieldName = $field->getName();

        if (! is_string($fieldName) || $fieldName === '') {
            return $field;
        }

        return $field->hintActions(self::hintActions(
            fieldName: $fieldName,
            format: $format,
            context: $context,
        ));
    }

    /**
     * @param  Closure|array<string, mixed>|null  $context
     * @return list<Action>
     */
    public static function hintActions(
        string $fieldName,
        ContentAssistFormat $format = ContentAssistFormat::Plain,
        Closure|array|null $context = null,
    ): array {
        if (! app(ModuleManager::class)->enabled(ProductModule::AI)) {
            return [];
        }

        $actionKey = str_replace(['.', '-', '[', ']'], '_', $fieldName);

        return [
            self::hintAction(
                name: "create{$actionKey}WithAi",
                label: 'Create',
                icon: 'heroicon-m-sparkles',
                fieldName: $fieldName,
                format: $format,
                operation: ContentAssistOperation::Create,
                context: $context,
            ),
            self::hintAction(
                name: "polish{$actionKey}WithAi",
                label: 'Polish',
                icon: 'heroicon-m-document-text',
                fieldName: $fieldName,
                format: $format,
                operation: ContentAssistOperation::Polish,
                context: $context,
            ),
            self::hintAction(
                name: "shorten{$actionKey}WithAi",
                label: 'Shorten',
                icon: 'heroicon-m-scissors',
                fieldName: $fieldName,
                format: $format,
                operation: ContentAssistOperation::Shorten,
                context: $context,
            ),
        ];
    }

    /**
     * @param  Closure|array<string, mixed>|null  $context
     */
    private static function hintAction(
        string $name,
        string $label,
        string $icon,
        string $fieldName,
        ContentAssistFormat $format,
        ContentAssistOperation $operation,
        Closure|array|null $context,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->action(function (Get $get, Set $set) use (
                $fieldName,
                $format,
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

                $content = self::normalizeIncomingContent(
                    $get($fieldName),
                    $format,
                );

                if (
                    $operation !== ContentAssistOperation::Create
                    && $content === ''
                ) {
                    Notification::make()
                        ->warning()
                        ->title('Add some text first')
                        ->body('AI can polish or shorten existing content.')
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

                $result = app(DocumentContentGenerator::class)->transform(
                    format: $format,
                    operation: $operation,
                    content: $content,
                    context: $resolvedContext,
                );

                if ($result === null) {
                    Notification::make()
                        ->danger()
                        ->title('AI content generation failed')
                        ->body("Unable to {$operation->value} this content. Try again or edit manually.")
                        ->send();

                    return;
                }

                $set($fieldName, $result);

                Notification::make()
                    ->success()
                    ->title('AI content updated')
                    ->send();
            });
    }

    private static function normalizeIncomingContent(
        mixed $content,
        ContentAssistFormat $format,
    ): string {
        if (is_array($content)) {
            $content = RichContentRenderer::make($content)->toHtml();
        }

        $content = trim((string) ($content ?? ''));

        if ($format === ContentAssistFormat::Plain) {
            return trim(html_entity_decode(strip_tags($content)));
        }

        return $content;
    }
}
