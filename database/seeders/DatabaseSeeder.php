<?php

namespace Database\Seeders;

use App\Enums\ProductModule;
use App\Models\SopRole;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
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

        if ($moduleManager->enabled(ProductModule::TMS)) {
            $this->call(TmsModuleSeeder::class);
        }

        if (app()->environment(['local', 'testing'])) {
            $user = User::firstOrCreate(
                ['email' => 'admin@example.com'],
                ['name' => 'Super Admin', 'password' => 'password'],
            );

            $roles = [
                Role::findOrCreate(Utils::getSuperAdminName(), 'web')->name,
                Role::findOrCreate(Utils::getPanelUserRoleName(), 'web')->name,
            ];

            if ($moduleManager->enabled(ProductModule::DMS)) {
                $roles[] = SopRole::ADMINISTRATOR;
            }

            $user->syncRoles($roles);
        }
    }
}
