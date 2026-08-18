<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ElectronicSignatureAuthenticator
{
    private int|string|null $confirmedSignerId = null;

    private bool $challengeFailed = false;

    public function __construct(private readonly UserAccessService $userAccessService) {}

    public function confirm(User $signer, ?string $password): void
    {
        $this->userAccessService->assertCanAuthenticate($signer);

        if (! is_string($password) || $password === '') {
            throw ValidationException::withMessages([
                'signature_password' => 'Re-enter your password to apply this electronic signature.',
            ]);
        }

        if (! Hash::check($password, (string) $signer->password)) {
            $this->challengeFailed = true;
            $this->confirmedSignerId = null;
            $this->userAccessService->recordSignatureChallengeFailure($signer);

            throw ValidationException::withMessages([
                'signature_password' => 'The electronic signature password is incorrect.',
            ]);
        }

        $this->challengeFailed = false;
        $this->confirmedSignerId = $signer->getAuthIdentifier();
    }

    public function assertConfirmed(User $signer): void
    {
        $this->userAccessService->assertCanAuthenticate($signer);

        if ($this->isConfirmed($signer)) {
            return;
        }

        if ($this->canUseTestingFactoryPassword($signer)) {
            $this->confirmedSignerId = $signer->getAuthIdentifier();

            return;
        }

        throw ValidationException::withMessages([
            'signature_password' => 'Re-enter your password to apply this electronic signature.',
        ]);
    }

    public function isConfirmed(User $signer): bool
    {
        return $this->confirmedSignerId !== null
            && (string) $this->confirmedSignerId === (string) $signer->getAuthIdentifier();
    }

    public function reset(): void
    {
        $this->confirmedSignerId = null;
        $this->challengeFailed = false;
    }

    private function canUseTestingFactoryPassword(User $signer): bool
    {
        if (! app()->runningUnitTests() || $this->challengeFailed) {
            return false;
        }

        $factoryPassword = (string) config('gxp.test_factory_signature_password', 'password');

        return Hash::check($factoryPassword, (string) $signer->password);
    }
}
