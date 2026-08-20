<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Domain\Shared\Services\UserAccessService;
use App\Domain\Shared\Support\PasswordRules;
use App\Models\User;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class EditProfile extends BaseEditProfile
{
    private ?string $pendingNewPassword = null;

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/edit-profile.form.password.label'))
            ->validationAttribute(__('filament-panels::auth/pages/edit-profile.form.password.validation_attribute'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->rule(PasswordRules::required())
            ->showAllValidationMessages()
            ->autocomplete('new-password')
            ->dehydrated(fn (#[SensitiveParameter] $state): bool => filled($state))
            ->live(debounce: 500)
            ->same('passwordConfirmation');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(#[SensitiveParameter] array $data): array
    {
        if (filled($data['password'] ?? null)) {
            $this->pendingNewPassword = (string) $data['password'];
            unset($data['password']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        if ($record instanceof User && filled($this->pendingNewPassword)) {
            try {
                app(UserAccessService::class)->changeOwnPassword(
                    $record,
                    (string) ($this->data['currentPassword'] ?? ''),
                    $this->pendingNewPassword,
                );
            } catch (ValidationException $exception) {
                $messages = [];

                foreach ($exception->errors() as $key => $errors) {
                    $field = match ($key) {
                        'current_password' => 'data.currentPassword',
                        'password' => 'data.password',
                        default => 'data.'.$key,
                    };
                    $messages[$field] = $errors;
                }

                throw ValidationException::withMessages($messages);
            }
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function afterSave(): void
    {
        if (request()->hasSession() && filled($this->pendingNewPassword)) {
            $user = $this->getUser();

            if ($user instanceof User) {
                $user->refresh();
            }

            request()->session()->put([
                'password_hash_'.Filament::getAuthGuard() => $user->getAuthPassword(),
            ]);
        }

        $this->pendingNewPassword = null;
    }
}
