<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Enums\ProductLicenseAuditEventType;
use App\Enums\ProductLicenseState;
use App\Enums\ProductModule;
use App\Exceptions\InvalidSignedLicenseException;
use App\Models\ProductLicense;
use App\Support\Modules\Contracts\LicenseAuditRecorder;
use App\Support\Modules\Contracts\SignedLicenseActivator;
use App\Support\Modules\Contracts\SignedLicenseVerifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use JsonException;

class ValidatedSignedLicenseActivator implements SignedLicenseActivator
{
    public function __construct(
        private readonly SignedLicenseVerifier $verifier,
        private readonly LicenseAuditRecorder $auditRecorder,
    ) {}

    public function activate(string $payload, string $signature, string $keyId): ProductLicense
    {
        $license = new ProductLicense([
            'payload' => $payload,
            'signature' => $signature,
            'key_id' => $keyId,
        ]);

        if (! $this->verifier->isValid($license)) {
            throw InvalidSignedLicenseException::signature();
        }

        $claims = $this->validatedClaims($payload);
        $issuedAt = CarbonImmutable::parse($claims['issued_at']);
        $expiresAt = CarbonImmutable::parse($claims['expires_at']);

        return DB::transaction(function () use ($claims, $keyId, $payload, $signature, $issuedAt, $expiresAt): ProductLicense {
            $existingLicense = ProductLicense::query()
                ->where('license_key', $claims['license_key'])
                ->lockForUpdate()
                ->first();

            if ($existingLicense !== null) {
                return $this->replace(
                    $existingLicense,
                    $claims,
                    $keyId,
                    $payload,
                    $signature,
                    $issuedAt,
                    $expiresAt,
                );
            }

            $license = ProductLicense::query()->create([
                'license_key' => $claims['license_key'],
                'key_id' => $keyId,
                'payload' => $payload,
                'signature' => $signature,
                'activated_at' => now(),
                'issued_at' => $issuedAt,
                'expires_at' => $expiresAt,
                'grace_ends_at' => $expiresAt->addDays($claims['grace_days']),
                'last_verified_at' => now(),
            ]);

            $this->auditRecorder->record(
                $license,
                ProductLicenseAuditEventType::Activated,
                ProductLicenseState::Active,
                ['key_id' => $keyId, 'modules' => $claims['modules']],
            );

            return $license;
        });
    }

    /**
     * @param array{
     *     version: int,
     *     license_key: string,
     *     modules: list<string>,
     *     issued_at: string,
     *     expires_at: string,
     *     grace_days: int
     * } $claims
     */
    private function replace(
        ProductLicense $license,
        array $claims,
        string $keyId,
        string $payload,
        string $signature,
        CarbonImmutable $issuedAt,
        CarbonImmutable $expiresAt,
    ): ProductLicense {
        if ($license->issued_at === null || ! $issuedAt->isAfter($license->issued_at)) {
            throw InvalidSignedLicenseException::payload(
                'a replacement must have a later issued_at timestamp.',
            );
        }

        $previousKeyId = $license->key_id;

        $license->update([
            'key_id' => $keyId,
            'payload' => $payload,
            'signature' => $signature,
            'activated_at' => now(),
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'grace_ends_at' => $expiresAt->addDays($claims['grace_days']),
            'revoked_at' => null,
            'last_verified_at' => now(),
        ]);

        $this->auditRecorder->record(
            $license,
            ProductLicenseAuditEventType::Upgraded,
            ProductLicenseState::Active,
            [
                'from_key_id' => $previousKeyId,
                'to_key_id' => $keyId,
                'modules' => $claims['modules'],
            ],
        );

        return $license->refresh();
    }

    /**
     * @return array{
     *     version: int,
     *     license_key: string,
     *     modules: list<string>,
     *     issued_at: string,
     *     expires_at: string,
     *     grace_days: int
     * }
     */
    private function validatedClaims(string $payload): array
    {
        try {
            $claims = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidSignedLicenseException::payload('malformed JSON.');
        }

        if (! is_array($claims)) {
            throw InvalidSignedLicenseException::payload('the root claim must be an object.');
        }

        $validator = Validator::make($claims, [
            'version' => ['required', 'integer', 'in:1'],
            'license_key' => ['required', 'string', 'uuid'],
            'modules' => ['required', 'array', 'min:1'],
            'modules.*' => ['required', 'string', 'distinct', Rule::enum(ProductModule::class)],
            'issued_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:issued_at'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:90'],
        ]);

        if ($validator->fails()) {
            throw InvalidSignedLicenseException::payload($validator->errors()->first());
        }

        /** @var array{version: int, license_key: string, modules: list<string>, issued_at: string, expires_at: string, grace_days: int} $validated */
        $validated = $validator->validated();

        $modules = array_map(
            fn (string $module): ProductModule => ProductModule::from($module),
            $validated['modules'],
        );

        foreach ($modules as $module) {
            foreach ($module->dependencies() as $dependency) {
                if (! in_array($dependency, $modules, true)) {
                    throw InvalidSignedLicenseException::payload(
                        "{$module->value} requires the {$dependency->value} module.",
                    );
                }
            }
        }

        return $validated;
    }
}
