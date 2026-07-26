<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class InternalAuditSeeder extends Seeder
{
    public function run(): void
    {
        // Internal audits contain organization-specific quality data and are never seeded.
    }
}
