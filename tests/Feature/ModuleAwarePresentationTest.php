<?php

declare(strict_types=1);

use App\Enums\ProductModule;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\AiExecutions\Widgets\AiExecutionOverview;
use App\Filament\Resources\AiExecutions\Widgets\AiProviderPerformanceTable;
use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use App\Filament\Widgets\DocumentsByStatusChart;
use App\Filament\Widgets\DocumentsCreatedChart;
use App\Filament\Widgets\DocumentStatsOverview;
use App\Filament\Widgets\PendingApprovalsTable;
use App\Filament\Widgets\RecentAuditActivityTable;

it('allows DMS dashboard and metrics when DMS is enabled', function (): void {
    config()->set('modules.enabled', ['dms']);

    expect(Dashboard::canAccess())->toBeTrue()
        ->and(DocumentStatsOverview::canView())->toBeTrue()
        ->and(DocumentsByStatusChart::canView())->toBeTrue()
        ->and(DocumentsCreatedChart::canView())->toBeTrue()
        ->and(PendingApprovalsTable::canView())->toBeTrue()
        ->and(RecentAuditActivityTable::canView())->toBeTrue();
});

it('declares explicit module ownership for reporting metrics', function (): void {
    expect(DocumentStatsOverview::productModule())->toBe(ProductModule::DMS)
        ->and(DocumentsByStatusChart::productModule())->toBe(ProductModule::DMS)
        ->and(DocumentsCreatedChart::productModule())->toBe(ProductModule::DMS)
        ->and(PendingApprovalsTable::productModule())->toBe(ProductModule::DMS)
        ->and(RecentAuditActivityTable::productModule())->toBe(ProductModule::DMS)
        ->and(AiExecutionOverview::productModule())->toBe(ProductModule::AI)
        ->and(AiProviderPerformanceTable::productModule())->toBe(ProductModule::AI);
});

it('enforces AI entitlement directly on AI reporting metrics', function (): void {
    config()->set('modules.enabled', ['dms']);

    expect(AiExecutionOverview::canView())->toBeFalse()
        ->and(AiProviderPerformanceTable::canView())->toBeFalse();

    config()->set('modules.enabled', ['dms', 'ai']);

    expect(AiExecutionOverview::canView())->toBeTrue()
        ->and(AiProviderPerformanceTable::canView())->toBeTrue();
});

it('enforces DMS entitlement on settings presentation and direct routes', function (): void {
    config()->set('modules.enabled', []);

    expect(SettingsCluster::canAccess())->toBeFalse();

    $this->get(DocumentTypeResource::getUrl())->assertNotFound();
});

it('blocks DMS dashboard and metrics when DMS is disabled', function (): void {
    config()->set('modules.enabled', []);

    expect(Dashboard::canAccess())->toBeFalse()
        ->and(DocumentStatsOverview::canView())->toBeFalse()
        ->and(DocumentsByStatusChart::canView())->toBeFalse()
        ->and(DocumentsCreatedChart::canView())->toBeFalse()
        ->and(PendingApprovalsTable::canView())->toBeFalse()
        ->and(RecentAuditActivityTable::canView())->toBeFalse();
});
