<?php

declare(strict_types=1);

use App\Enums\ProductModule;
use App\Models\ProductLicense;
use App\Support\Modules\Contracts\SignedLicenseVerifier;
use App\Support\Modules\ModuleManager;
use App\Support\Modules\OpenSslSignedLicenseVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $privateKey = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    expect($privateKey)->not->toBeFalse();

    $keyDetails = openssl_pkey_get_details($privateKey);

    expect($keyDetails)->not->toBeFalse();

    $this->licensePrivateKey = $privateKey;
    $this->licensePublicKey = $keyDetails['key'];
    config()->set('modules.license.public_keys.test-key', $this->licensePublicKey);
});

it('persists signed license material and lifecycle timestamps', function (): void {
    $license = ProductLicense::factory()->create();

    expect($license->fresh())
        ->license_key->toBe($license->license_key)
        ->key_id->toBe('test-key')
        ->activated_at->not->toBeNull()
        ->expires_at->not->toBeNull()
        ->grace_ends_at->not->toBeNull();
});

it('verifies an untampered license payload with its trusted issuer key', function (): void {
    $payload = json_encode([
        'license_key' => fake()->uuid(),
        'modules' => ['dms', 'qms'],
    ], JSON_THROW_ON_ERROR);

    expect(openssl_sign(
        $payload,
        $signature,
        $this->licensePrivateKey,
        OPENSSL_ALGO_SHA256,
    ))->toBeTrue();

    $license = ProductLicense::factory()->make([
        'payload' => $payload,
        'signature' => base64_encode($signature),
    ]);

    expect(app(SignedLicenseVerifier::class))
        ->toBeInstanceOf(OpenSslSignedLicenseVerifier::class)
        ->and(app(SignedLicenseVerifier::class)->isValid($license))->toBeTrue();
});

it('rejects tampered payloads malformed signatures and unknown keys', function (): void {
    $payload = '{"license_key":"original","modules":["dms"]}';

    expect(openssl_sign(
        $payload,
        $signature,
        $this->licensePrivateKey,
        OPENSSL_ALGO_SHA256,
    ))->toBeTrue();

    $license = ProductLicense::factory()->make([
        'payload' => $payload,
        'signature' => base64_encode($signature),
    ]);

    $license->payload = '{"license_key":"tampered","modules":["dms","qms"]}';

    expect(app(SignedLicenseVerifier::class)->isValid($license))->toBeFalse();

    $license->payload = $payload;
    $license->signature = 'not-valid-base64!';

    expect(app(SignedLicenseVerifier::class)->isValid($license))->toBeFalse();

    $license->signature = base64_encode($signature);
    $license->key_id = 'unknown-key';

    expect(app(SignedLicenseVerifier::class)->isValid($license))->toBeFalse();
});

it('keeps environment module configuration authoritative for now', function (): void {
    ProductLicense::factory()->create([
        'payload' => '{"modules":["dms","qms","ai"]}',
    ]);

    config()->set('modules.enabled', ['dms']);

    expect(app(ModuleManager::class)->enabledModules())
        ->toBe([ProductModule::DMS]);
});
