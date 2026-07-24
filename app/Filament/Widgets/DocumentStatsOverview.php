<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ProductModule;
use App\Filament\Resources\DocumentIssuances\DocumentIssuanceResource;
use App\Filament\Resources\SopApprovals\SopApprovalResource;
use App\Filament\Resources\SopDocuments\SopDocumentResource;
use App\Filament\Resources\SopTemplates\SopTemplateResource;
use App\Models\DocumentIssuance;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\SopApproval;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\TemplateStatus;
use App\Support\Modules\ModuleManager;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DocumentStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Document Control Overview';

    protected ?string $description = 'Key metrics across SOPs, approvals, and controlled copies.';

    public static function canView(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::DMS);
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        $pendingApprovals = $user !== null
            ? SopApproval::query()->actionableFor($user)->count()
            : SopApproval::query()->pending()->count();

        $effectiveSops = SopDocument::query()
            ->where('document_status_id', DocumentStatus::idFor(DocumentStatus::EFFECTIVE))
            ->whereHas('documentType', fn ($query) => $query->where('code', DocumentType::SOP))
            ->count();

        $underReview = SopDocument::query()
            ->where('document_status_id', DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW))
            ->count();

        $dueForReview = SopDocument::query()
            ->where('document_status_id', DocumentStatus::idFor(DocumentStatus::EFFECTIVE))
            ->whereNotNull('review_date')
            ->whereDate('review_date', '<=', now())
            ->count();

        $activeCopies = DocumentIssuance::query()->active()->count();

        $publishedTemplates = SopTemplate::query()
            ->where('template_status_id', TemplateStatus::idFor(TemplateStatus::PUBLISHED))
            ->count();

        return [
            Stat::make('Pending Approvals', $pendingApprovals)
                ->description('Awaiting action in your queue')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($pendingApprovals > 0 ? 'warning' : 'success')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->url(SopApprovalResource::getUrl()),
            Stat::make('Effective SOPs', $effectiveSops)
                ->description('Active standard operating procedures')
                ->descriptionIcon(Heroicon::OutlinedDocumentCheck)
                ->color('success')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->url(SopDocumentResource::getUrl()),
            Stat::make('Under Review', $underReview)
                ->description('Documents in approval workflow')
                ->descriptionIcon(Heroicon::OutlinedArrowPath)
                ->color('info')
                ->icon(Heroicon::OutlinedDocumentMagnifyingGlass)
                ->url(SopDocumentResource::getUrl()),
            Stat::make('Due for Review', $dueForReview)
                ->description('Past scheduled review date')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($dueForReview > 0 ? 'danger' : 'gray')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->url(SopDocumentResource::getUrl()),
            Stat::make('Active Copies', $activeCopies)
                ->description('Controlled copies in circulation')
                ->descriptionIcon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color('primary')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->url(DocumentIssuanceResource::getUrl()),
            Stat::make('Published Templates', $publishedTemplates)
                ->description('Ready for document generation')
                ->descriptionIcon(Heroicon::OutlinedBookOpen)
                ->color('gray')
                ->icon(Heroicon::OutlinedDocumentText)
                ->url(SopTemplateResource::getUrl()),
        ];
    }
}
