<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class AuditFindingSeeder extends Seeder
{
    public function run(): void
    {
        // Audit findings contain organization-specific quality data and are never seeded.
    }
}
