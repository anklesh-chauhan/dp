<?php

declare(strict_types=1);

use App\Enums\ProductModule;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ManageNumberSeriesSettings;
use App\Filament\Resources\AiExecutions\Widgets\AiExecutionOverview;
use App\Filament\Resources\AiExecutions\Widgets\AiProviderPerformanceTable;
use App\Filament\Resources\ApprovalDecisions\ApprovalDecisionResource;
use App\Filament\Resources\ApprovalStepTypes\ApprovalStepTypeResource;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\DocumentCategories\DocumentCategoryResource;
use App\Filament\Resources\DocumentIssuances\DocumentIssuanceResource;
use App\Filament\Resources\DocumentStatuses\DocumentStatusResource;
use App\Filament\Resources\DocumentTemplates\DocumentTemplateResource;
use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use App\Filament\Resources\IssuanceStatuses\IssuanceStatusResource;
use App\Filament\Resources\KnowledgeGuides\KnowledgeGuideResource;
use App\Filament\Resources\LogDocuments\LogDocumentResource;
use App\Filament\Resources\LookupResource;
use App\Filament\Resources\NumberSeries\NumberSeriesResource;
use App\Filament\Resources\RegulationTags\RegulationTagResource;
use App\Filament\Resources\SopApprovals\SopApprovalResource;
use App\Filament\Resources\SopRoles\SopRoleResource;
use App\Filament\Resources\SopWorkflows\SopWorkflowResource;
use App\Filament\Resources\TemplateStatuses\TemplateStatusResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\VariableDataTypes\VariableDataTypeResource;
use App\Filament\Widgets\DocumentsByStatusChart;
use App\Filament\Widgets\DocumentsCreatedChart;
use App\Filament\Widgets\DocumentStatsOverview;
use App\Filament\Widgets\PendingApprovalsTable;
use App\Filament\Widgets\RecentAuditActivityTable;

it('assigns DMS presentation surfaces to explicit navigation groups', function (): void {
    expect(Dashboard::getNavigationLabel())->toBe('DMS Dashboard')
        ->and(ControlledDocumentResource::getNavigationGroup())->toBe('DMS · Document Control')
        ->and(DocumentTemplateResource::getNavigationGroup())->toBe('DMS · Document Control')
        ->and(SopWorkflowResource::getNavigationGroup())->toBe('DMS · Document Control')
        ->and(SopApprovalResource::getNavigationGroup())->toBe('DMS · Document Control')
        ->and(LookupResource::getNavigationGroup())->toBe('DMS · Document Control')
        ->and(DocumentIssuanceResource::getNavigationGroup())->toBe('DMS · Issuance')
        ->and(LogDocumentResource::getNavigationGroup())->toBe('DMS · Issuance')
        ->and(KnowledgeGuideResource::getNavigationGroup())->toBe('DMS · Help & Knowledge');
});

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

it('separates Core organization and identity navigation from DMS configuration', function (): void {
    expect(SettingsCluster::getNavigationLabel())->toBe('DMS Settings')
        ->and(SettingsCluster::getNavigationGroup())->toBe('DMS · Settings')
        ->and(DepartmentResource::getCluster())->toBeNull()
        ->and(DepartmentResource::getNavigationGroup())->toBe('Core · Organization')
        ->and(UserResource::getNavigationGroup())->toBe('Core · Identity & Access')
        ->and(DocumentTypeResource::getNavigationGroup())->toBe('DMS Configuration')
        ->and(DocumentCategoryResource::getNavigationGroup())->toBe('DMS Configuration')
        ->and(DocumentStatusResource::getNavigationGroup())->toBe('DMS Configuration')
        ->and(TemplateStatusResource::getNavigationGroup())->toBe('DMS Configuration')
        ->and(VariableDataTypeResource::getNavigationGroup())->toBe('DMS Configuration')
        ->and(ApprovalDecisionResource::getNavigationGroup())->toBe('DMS Configuration')
        ->and(ApprovalStepTypeResource::getNavigationGroup())->toBe('DMS Configuration')
        ->and(SopRoleResource::getNavigationGroup())->toBe('DMS Configuration')
        ->and(IssuanceStatusResource::getNavigationGroup())->toBe('DMS Configuration')
        ->and(NumberSeriesResource::getNavigationGroup())->toBe('DMS Configuration')
        ->and(RegulationTagResource::getNavigationGroup())->toBe('DMS Configuration')
        ->and(ManageNumberSeriesSettings::getNavigationGroup())->toBe('DMS Configuration');
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
