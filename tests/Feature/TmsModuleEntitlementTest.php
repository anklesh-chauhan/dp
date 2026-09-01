<?php

declare(strict_types=1);

use App\Enums\ProductModule;
use App\Exceptions\ModuleNotEnabledException;
use App\Filament\Pages\MyTraining;
use App\Filament\Pages\Reports\TrainingMatrixReportPage;
use App\Filament\Resources\TrainingAssignments\TrainingAssignmentResource;
use App\Support\Modules\ModuleManager;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('reports TMS and its DMS dependency as enabled when configured', function (): void {
    config()->set('modules.enabled', ['dms', 'tms']);

    $moduleManager = app(ModuleManager::class);

    expect($moduleManager->enabled(ProductModule::TMS))->toBeTrue()
        ->and($moduleManager->enabledModules())->toContain(ProductModule::TMS);
});

it('disables TMS when DMS is unavailable', function (): void {
    config()->set('modules.enabled', ['tms']);

    expect(app(ModuleManager::class)->enabled(ProductModule::TMS))->toBeFalse();
});

it('throws a module exception when TMS is disabled', function (): void {
    config()->set('modules.enabled', ['dms']);

    expect(fn () => app(ModuleManager::class)->ensureEnabled(ProductModule::TMS))
        ->toThrow(ModuleNotEnabledException::class);
});

it('protects routes with the TMS module middleware', function (): void {
    Route::middleware('module:tms')->get('/test-tms-module', fn (): string => 'TMS enabled');

    config()->set('modules.enabled', ['dms']);
    $this->get('/test-tms-module')->assertNotFound();

    config()->set('modules.enabled', ['dms', 'tms']);
    $this->get('/test-tms-module')->assertOk()->assertSee('TMS enabled');
});

it('seeds TMS permissions only when TMS is enabled', function (): void {
    config()->set('modules.enabled', ['dms', 'tms']);

    $this->seed(DatabaseSeeder::class);

    foreach (TmsModuleSeeder::PERMISSIONS as $permission) {
        expect(Permission::query()->where('name', $permission)->exists())->toBeTrue();
    }
});

it('does not expose TMS Filament surfaces when the module is disabled', function (): void {
    config()->set('modules.enabled', ['dms']);

    expect(MyTraining::canAccess())->toBeFalse()
        ->and(TrainingAssignmentResource::canAccess())->toBeFalse()
        ->and(TrainingAssignmentResource::shouldRegisterNavigation())->toBeFalse();
});

it('labels TMS as Training Management', function (): void {
    expect(ProductModule::TMS->label())->toBe('Training Management')
        ->and(ProductModule::TMS->dependencies())->toBe([ProductModule::DMS]);
});

it('registers TMS report pages under the TMS reports navigation group', function (): void {
    config()->set('modules.enabled', ['dms', 'tms']);

    expect(TrainingMatrixReportPage::getNavigationGroup())->toBe('TMS · Reports');
});
