<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Models\ProductLicense;
use App\Support\Modules\Contracts\SignedLicenseVerifier;
use Illuminate\Contracts\Config\Repository;

class OpenSslSignedLicenseVerifier implements SignedLicenseVerifier
{
    public function __construct(private readonly Repository $config) {}

    public function isValid(ProductLicense $license): bool
    {
        $publicKey = $this->config->get("modules.license.public_keys.{$license->key_id}");
        $signature = base64_decode($license->signature, true);

        if (! is_string($publicKey) || blank($publicKey) || $signature === false) {
            return false;
        }

        $key = openssl_pkey_get_public($publicKey);

        if ($key === false) {
            return false;
        }

        return openssl_verify(
            $license->payload,
            $signature,
            $key,
            OPENSSL_ALGO_SHA256,
        ) === 1;
    }
}
