<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->call(SopModuleSeeder::class);

        $user = User::firstOrCreate(['email' => 'admin@example.com'], ['name' => 'Super Admin', 'password' => bcrypt('password')]);
        $user->assignRole('super_admin');
    }
}
