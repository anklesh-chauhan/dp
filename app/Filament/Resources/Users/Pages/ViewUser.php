<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Shared\Services\UserAccessService;
use App\Domain\Shared\Support\PasswordRules;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('resetPassword')
                ->label('Reset password')
                ->visible(fn (): bool => (bool) auth()->user()?->can('Update:User'))
                ->schema([
                    TextInput::make('temporary_password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->rule(PasswordRules::required()),
                    Textarea::make('reason')->required()->rows(3),
                    TextInput::make('signature_password')
                        ->label('Electronic signature password')
                        ->password()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var User $actor */
                    $actor = auth()->user();
                    app(UserAccessService::class)->resetPassword(
                        $actor,
                        $this->record,
                        $data['temporary_password'],
                        $data['reason'],
                    );
                    Notification::make()->success()->title('Temporary password issued. The user must change it at next login.')->send();
                }),
            Action::make('deactivate')
                ->label('Deactivate')
                ->color('danger')
                ->visible(fn (): bool => $this->record->deactivated_at === null && (bool) auth()->user()?->can('Update:User'))
                ->schema([
                    Textarea::make('reason')->required()->rows(3),
                    TextInput::make('signature_password')
                        ->label('Electronic signature password')
                        ->password()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var User $actor */
                    $actor = auth()->user();
                    if ($actor->is($this->record)) {
                        throw ValidationException::withMessages([
                            'reason' => 'You cannot deactivate your own account.',
                        ]);
                    }
                    app(UserAccessService::class)->deactivate($actor, $this->record, $data['reason']);
                    $this->record->refresh();
                    Notification::make()->success()->title('Account deactivated')->send();
                }),
            Action::make('reactivate')
                ->label('Reactivate')
                ->visible(fn (): bool => $this->record->deactivated_at !== null && (bool) auth()->user()?->can('Update:User'))
                ->schema([
                    Textarea::make('reason')->required()->rows(3),
                    TextInput::make('signature_password')
                        ->label('Electronic signature password')
                        ->password()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var User $actor */
                    $actor = auth()->user();
                    app(UserAccessService::class)->reactivate($actor, $this->record, $data['reason']);
                    $this->record->refresh();
                    Notification::make()->success()->title('Account reactivated')->send();
                }),
            Action::make('unlock')
                ->label('Unlock')
                ->visible(fn (): bool => $this->record->locked_at !== null && (bool) auth()->user()?->can('Update:User'))
                ->schema([
                    Textarea::make('reason')->required()->rows(3),
                    TextInput::make('signature_password')
                        ->label('Electronic signature password')
                        ->password()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var User $actor */
                    $actor = auth()->user();
                    app(UserAccessService::class)->unlock($actor, $this->record, $data['reason']);
                    $this->record->refresh();
                    Notification::make()->success()->title('Account unlocked')->send();
                }),
        ];
    }
}
