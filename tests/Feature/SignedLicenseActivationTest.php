<?php

declare(strict_types=1);

use App\Exceptions\InvalidSignedLicenseException;
use App\Models\ProductLicense;
use App\Support\Modules\Contracts\SignedLicenseActivator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->privateKey = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    $details = openssl_pkey_get_details($this->privateKey);

    config()->set('modules.license.public_keys.activation-key', $details['key']);
});

it('activates a verified license and derives lifecycle metadata', function (): void {
    $payload = validLicensePayload();
    $signature = signLicensePayload($payload, $this->privateKey);

    $license = app(SignedLicenseActivator::class)->activate(
        $payload,
        $signature,
        'activation-key',
    );

    expect($license)
        ->toBeInstanceOf(ProductLicense::class)
        ->activated_at->not->toBeNull()
        ->last_verified_at->not->toBeNull()
        ->and($license->issued_at?->toIso8601String())->toBe('2026-07-25T00:00:00+00:00')
        ->and($license->expires_at?->toIso8601String())->toBe('2027-07-25T00:00:00+00:00')
        ->and($license->grace_ends_at?->toIso8601String())->toBe('2027-08-08T00:00:00+00:00')
        ->and(ProductLicense::query()->count())->toBe(1);
});

it('does not persist a license with an invalid signature', function (): void {
    expect(fn () => app(SignedLicenseActivator::class)->activate(
        validLicensePayload(),
        base64_encode('invalid'),
        'activation-key',
    ))->toThrow(InvalidSignedLicenseException::class, 'signature is invalid');

    expect(ProductLicense::query()->count())->toBe(0);
});

it('rejects invalid claims after signature verification without persistence', function (array $claims): void {
    $payload = json_encode($claims, JSON_THROW_ON_ERROR);
    $signature = signLicensePayload($payload, $this->privateKey);

    expect(fn () => app(SignedLicenseActivator::class)->activate(
        $payload,
        $signature,
        'activation-key',
    ))->toThrow(InvalidSignedLicenseException::class);

    expect(ProductLicense::query()->count())->toBe(0);
})->with([
    'unsupported payload version' => [[
        'version' => 2,
        'license_key' => 'eb56f686-6aa0-4a9d-b45d-39834008575d',
        'modules' => ['dms'],
        'issued_at' => '2026-07-25T00:00:00+00:00',
        'expires_at' => '2027-07-25T00:00:00+00:00',
        'grace_days' => 14,
    ]],
    'missing DMS dependency' => [[
        'version' => 1,
        'license_key' => 'eb56f686-6aa0-4a9d-b45d-39834008575d',
        'modules' => ['qms'],
        'issued_at' => '2026-07-25T00:00:00+00:00',
        'expires_at' => '2027-07-25T00:00:00+00:00',
        'grace_days' => 14,
    ]],
    'expiry before issue date' => [[
        'version' => 1,
        'license_key' => 'eb56f686-6aa0-4a9d-b45d-39834008575d',
        'modules' => ['dms'],
        'issued_at' => '2027-07-25T00:00:00+00:00',
        'expires_at' => '2026-07-25T00:00:00+00:00',
        'grace_days' => 14,
    ]],
]);

function validLicensePayload(): string
{
    return json_encode([
        'version' => 1,
        'license_key' => 'eb56f686-6aa0-4a9d-b45d-39834008575d',
        'modules' => ['dms', 'qms'],
        'issued_at' => '2026-07-25T00:00:00+00:00',
        'expires_at' => '2027-07-25T00:00:00+00:00',
        'grace_days' => 14,
    ], JSON_THROW_ON_ERROR);
}

function signLicensePayload(string $payload, OpenSSLAsymmetricKey $privateKey): string
{
    expect(openssl_sign(
        $payload,
        $signature,
        $privateKey,
        OPENSSL_ALGO_SHA256,
    ))->toBeTrue();

    return base64_encode($signature);
}
