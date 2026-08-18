<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Shared\Services\ElectronicSignatureAuthenticator;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Contracts\Events\ShouldBeDiscovered;
use Illuminate\Support\Facades\Auth;

class ConfirmElectronicSignatureFromAction implements ShouldBeDiscovered
{
    public function __construct(private readonly ElectronicSignatureAuthenticator $authenticator) {}

    public static function shouldBeDiscovered(): bool
    {
        return false;
    }

    /**
     * Filament dispatches ActionCalling as Event::dispatch(ActionCalling::class, $action),
     * so the payload is the Action instance, not the event object.
     */
    public function handle(Action $action): void
    {
        $data = $action->getData();
        $password = $data['signature_password'] ?? null;

        if (! is_string($password) || $password === '') {
            return;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $this->authenticator->confirm($user, $password);
    }
}
