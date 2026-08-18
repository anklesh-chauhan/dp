<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\DMS\Services\KnowledgeLessonService;
use App\Models\KnowledgeLesson;
use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

final class CaptureKnowledgeLessonAction
{
    /**
     * @param  Closure(): Model  $source
     */
    public static function make(Closure $source): Action
    {
        return Action::make('captureLesson')
            ->label('Capture lesson learned')
            ->schema([
                TextInput::make('title')->required()->maxLength(255),
                Textarea::make('summary')->required()->rows(3),
                Textarea::make('body')->required()->rows(8),
            ])
            ->visible(fn (): bool => (bool) auth()->user()?->can('create', KnowledgeLesson::class))
            ->action(function (array $data) use ($source): void {
                /** @var User $actor */
                $actor = auth()->user();

                $lesson = app(KnowledgeLessonService::class)->recordFrom(
                    $source(),
                    $actor,
                    $data['title'],
                    $data['summary'],
                    $data['body'],
                );

                Notification::make()
                    ->success()
                    ->title('Lesson learned recorded')
                    ->body($lesson->lesson_number)
                    ->send();
            });
    }
}
