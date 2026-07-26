<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DeviationAuditEventSeeder extends Seeder
{
    public function run(): void
    {
        // Audit events are written only by real deviation transitions.
    }
}
