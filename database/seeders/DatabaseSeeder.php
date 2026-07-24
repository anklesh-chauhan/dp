<?php

namespace Database\Seeders;

use App\Enums\ProductModule;
use App\Models\SopRole;
use App\Models\User;
use App\Support\Modules\ModuleManager;
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

        $moduleManager = app(ModuleManager::class);

        $this->call(CoreModuleSeeder::class);

        if ($moduleManager->enabled(ProductModule::DMS)) {
            $this->call(DmsModuleSeeder::class);
        }

        if ($moduleManager->enabled(ProductModule::AI)) {
            $this->call(AiModuleSeeder::class);
        }

        if ($moduleManager->enabled(ProductModule::QMS)) {
            $this->call(QmsModuleSeeder::class);
        }

        $user = User::firstOrCreate(['email' => 'admin@example.com'], ['name' => 'Super Admin', 'password' => bcrypt('password')]);

        if ($moduleManager->enabled(ProductModule::DMS)) {
            $user->assignRole(SopRole::ADMINISTRATOR);
        }
    }
}
