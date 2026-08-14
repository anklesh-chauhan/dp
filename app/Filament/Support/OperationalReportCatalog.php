<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Enums\ProductModule;
use App\Filament\Pages\Reports\DocumentRegisterReportPage;
use App\Filament\Pages\Reports\GmpExecutionReportPage;
use App\Filament\Pages\Reports\IssuanceRegisterReportPage;
use App\Filament\Pages\Reports\PendingApprovalsReportPage;
use App\Filament\Pages\Reports\PeriodicReviewReportPage;
use App\Filament\Pages\Reports\SopWhereUsedReportPage;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Collection;

final class OperationalReportCatalog
{
    /**
     * @return list<OperationalReport>
     */
    public function all(): array
    {
        return [
            new OperationalReport(
                key: 'document-register',
                module: ProductModule::DMS,
                title: 'Document Register',
                description: 'Master list of controlled documents with type, version, status, owner, and review dates.',
                permission: 'ViewAny:ControlledDocument',
                page: DocumentRegisterReportPage::class,
            ),
            new OperationalReport(
                key: 'sop-where-used',
                module: ProductModule::DMS,
                title: 'SOP Where-Used',
                description: 'Forms, logs, and other controlled documents that reference each SOP. One SOP can appear against many dependents.',
                permission: 'ViewAny:ControlledDocument',
                page: SopWhereUsedReportPage::class,
            ),
            new OperationalReport(
                key: 'periodic-review',
                module: ProductModule::DMS,
                title: 'Periodic Review',
                description: 'Effective documents that are overdue or due for scheduled review within 30 days.',
                permission: 'ViewAny:ControlledDocument',
                page: PeriodicReviewReportPage::class,
            ),
            new OperationalReport(
                key: 'pending-approvals',
                module: ProductModule::DMS,
                title: 'Pending Approvals',
                description: 'Controlled documents currently under review, including the waiting workflow step.',
                permission: 'ViewAny:ControlledDocument',
                page: PendingApprovalsReportPage::class,
            ),
            new OperationalReport(
                key: 'issuance-register',
                module: ProductModule::DMS,
                title: 'Issuance Register',
                description: 'Controlled copies in circulation, recalled, or destroyed, with recipient and issuer.',
                permission: 'ViewAny:DocumentIssuance',
                page: IssuanceRegisterReportPage::class,
            ),
            new OperationalReport(
                key: 'gmp-executions',
                module: ProductModule::DMS,
                title: 'GMP Executions',
                description: 'Writable execution records with status, disposition, batch, and supervisor/QA progress.',
                permission: 'ViewAny:DocumentExecution',
                page: GmpExecutionReportPage::class,
            ),
        ];
    }

    /**
     * @return Collection<int, OperationalReport>
     */
    public function visible(?User $user = null): Collection
    {
        $user ??= auth()->user();
        $modules = app(ModuleManager::class);

        return collect($this->all())
            ->filter(fn (OperationalReport $report): bool => $modules->enabled($report->module)
                && $user?->can($report->permission))
            ->values();
    }

    /**
     * @return Collection<int, OperationalReport>
     */
    public function forModule(ProductModule $module, ?User $user = null): Collection
    {
        return $this->visible($user)
            ->filter(fn (OperationalReport $report): bool => $report->module === $module)
            ->values();
    }
}
