<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Exceptions\ServiceException;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class ServiceExceptionHandler
{
    /**
     * @param  callable(): mixed  $callback
     * @param  callable(mixed): void|null  $afterSuccess
     */
    public static function run(
        callable $callback,
        ?string $failureTitle = null,
        ?string $successTitle = null,
        ?callable $afterSuccess = null,
        ?string $successBody = null,
    ): mixed {
        try {
            $result = $callback();

            if ($successTitle !== null) {
                $notification = Notification::make()
                    ->success()
                    ->title($successTitle);

                if ($successBody !== null) {
                    $notification->body($successBody);
                }

                $notification->send();
            }

            if ($afterSuccess !== null) {
                $afterSuccess($result);
            }

            return $result;
        } catch (ServiceException $exception) {
            self::notify($exception, $failureTitle);

            return null;
        } catch (ValidationException $exception) {
            self::notifyValidation($exception, $failureTitle);

            return null;
        }
    }

    public static function notify(ServiceException $exception, ?string $title = null): void
    {
        Notification::make()
            ->danger()
            ->title($title ?? $exception->title())
            ->body($exception->getMessage())
            ->persistent()
            ->send();
    }

    public static function notifyValidation(ValidationException $exception, ?string $title = null): void
    {
        Notification::make()
            ->danger()
            ->title($title ?? 'Validation Error')
            ->body(collect($exception->errors())->flatten()->first() ?? 'Please review the form and try again.')
            ->persistent()
            ->send();
    }

    public static function notifyFailure(string $message, ?string $title = null): void
    {
        Notification::make()
            ->danger()
            ->title($title ?? 'Action Failed')
            ->body($message)
            ->persistent()
            ->send();
    }
}
