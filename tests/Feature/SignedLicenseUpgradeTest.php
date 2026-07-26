<?php

declare(strict_types=1);

use App\Enums\ProductLicenseAuditEventType;
use App\Enums\ProductModule;
use App\Exceptions\InvalidSignedLicenseException;
use App\Models\ProductLicense;
use App\Support\Modules\Contracts\SignedLicenseActivator;
use App\Support\Modules\SignedLicenseEntitlementProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->privateKey = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    $details = openssl_pkey_get_details($this->privateKey);

    config()->set('modules.license.public_keys.upgrade-key', $details['key']);
});

it('atomically upgrades the same license using a later signed issue time', function (): void {
    $original = activateUpgradeLicense(
        $this->privateKey,
        ['dms'],
        '2026-07-25T00:00:00+00:00',
        '2027-07-25T00:00:00+00:00',
    );

    expect(app(SignedLicenseEntitlementProvider::class)->modules())
        ->toBe([ProductModule::DMS]);

    $upgraded = activateUpgradeLicense(
        $this->privateKey,
        ['dms', 'qms'],
        '2026-08-25T00:00:00+00:00',
        '2028-07-25T00:00:00+00:00',
    );

    expect($upgraded->is($original))->toBeTrue()
        ->and(ProductLicense::query()->count())->toBe(1)
        ->and($upgraded->issued_at?->toIso8601String())->toBe('2026-08-25T00:00:00+00:00')
        ->and($upgraded->expires_at?->toIso8601String())->toBe('2028-07-25T00:00:00+00:00')
        ->and(app(SignedLicenseEntitlementProvider::class)->modules())->toBe([
            ProductModule::DMS,
            ProductModule::QMS,
        ]);

    $events = $upgraded->auditEvents()->orderBy('id')->get();

    expect($events)->toHaveCount(2)
        ->and($events->pluck('event_type')->all())->toBe([
            ProductLicenseAuditEventType::Activated,
            ProductLicenseAuditEventType::Upgraded,
        ])
        ->and($events->last()->from_state?->value)->toBe('active')
        ->and($events->last()->to_state?->value)->toBe('active')
        ->and($events->last()->context)->toMatchArray([
            'from_key_id' => 'upgrade-key',
            'to_key_id' => 'upgrade-key',
            'modules' => ['dms', 'qms'],
        ])
        ->and($events->last()->context)->not->toHaveKeys(['payload', 'signature']);
});

it('rejects equal or older replacements without changing the active license', function (string $replacementIssuedAt): void {
    $original = activateUpgradeLicense(
        $this->privateKey,
        ['dms', 'qms'],
        '2026-08-25T00:00:00+00:00',
        '2028-07-25T00:00:00+00:00',
    );
    $originalPayload = $original->payload;

    expect(fn () => activateUpgradeLicense(
        $this->privateKey,
        ['dms'],
        $replacementIssuedAt,
        '2029-07-25T00:00:00+00:00',
    ))->toThrow(
        InvalidSignedLicenseException::class,
        'replacement must have a later issued_at timestamp',
    );

    $persisted = $original->fresh();

    expect($persisted?->payload)->toBe($originalPayload)
        ->and($persisted?->auditEvents()->count())->toBe(1)
        ->and(app(SignedLicenseEntitlementProvider::class)->modules())->toBe([
            ProductModule::DMS,
            ProductModule::QMS,
        ]);
})->with([
    'equal issue time' => '2026-08-25T00:00:00+00:00',
    'older issue time' => '2026-07-25T00:00:00+00:00',
]);

it('leaves the existing entitlement untouched when replacement verification fails', function (): void {
    $original = activateUpgradeLicense(
        $this->privateKey,
        ['dms', 'ai'],
        '2026-07-25T00:00:00+00:00',
        '2028-07-25T00:00:00+00:00',
    );

    $replacementPayload = upgradeLicensePayload(
        ['dms', 'qms'],
        '2026-08-25T00:00:00+00:00',
        '2029-07-25T00:00:00+00:00',
    );

    expect(fn () => app(SignedLicenseActivator::class)->activate(
        $replacementPayload,
        base64_encode('invalid'),
        'upgrade-key',
    ))->toThrow(InvalidSignedLicenseException::class, 'signature is invalid');

    expect($original->fresh()?->payload)->toBe($original->payload)
        ->and(ProductLicense::query()->count())->toBe(1)
        ->and(app(SignedLicenseEntitlementProvider::class)->modules())->toBe([
            ProductModule::DMS,
            ProductModule::AI,
        ]);
});

/**
 * @param  list<string>  $modules
 */
function activateUpgradeLicense(
    OpenSSLAsymmetricKey $privateKey,
    array $modules,
    string $issuedAt,
    string $expiresAt,
): ProductLicense {
    $payload = upgradeLicensePayload($modules, $issuedAt, $expiresAt);

    expect(openssl_sign(
        $payload,
        $signature,
        $privateKey,
        OPENSSL_ALGO_SHA256,
    ))->toBeTrue();

    return app(SignedLicenseActivator::class)->activate(
        $payload,
        base64_encode($signature),
        'upgrade-key',
    );
}

/**
 * @param  list<string>  $modules
 */
function upgradeLicensePayload(array $modules, string $issuedAt, string $expiresAt): string
{
    return json_encode([
        'version' => 1,
        'license_key' => 'be22c24a-cbf9-4564-b355-63c581c01388',
        'modules' => $modules,
        'issued_at' => $issuedAt,
        'expires_at' => $expiresAt,
        'grace_days' => 14,
    ], JSON_THROW_ON_ERROR);
}
