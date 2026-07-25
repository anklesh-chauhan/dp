<?php

namespace App\Providers;

use App\Domain\DMS\Services\SopApprovalDecisionAuthorizationAdapter;
use App\Domain\DMS\Services\SopApprovalDecisionOutcomeAdapter;
use App\Domain\DMS\Services\SopApprovalDecisionPersistenceAdapter;
use App\Domain\DMS\Services\SopApprovalPersistenceAdapter;
use App\Domain\DMS\Services\SopApprovalSubmissionAuthorizationAdapter;
use App\Domain\DMS\Services\SopApprovalSubmissionLifecycleAdapter;
use App\Domain\DMS\Services\SopWorkflowDefinitionSelector;
use App\Domain\Shared\Contracts\ApprovalDecisionAuthorization;
use App\Domain\Shared\Contracts\ApprovalDecisionOutcome;
use App\Domain\Shared\Contracts\ApprovalDecisionPersistence;
use App\Domain\Shared\Contracts\ApprovalInstancePersistence;
use App\Domain\Shared\Contracts\ApprovalSubmissionAuthorization;
use App\Domain\Shared\Contracts\ApprovalSubmissionLifecycle;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinitionSelector;
use App\Models\DocumentType;
use App\Observers\DocumentTypeObserver;
use App\Support\Sop\VariableTypes\VariableTypeRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ApprovalDecisionAuthorization::class, SopApprovalDecisionAuthorizationAdapter::class);
        $this->app->bind(ApprovalDecisionOutcome::class, SopApprovalDecisionOutcomeAdapter::class);
        $this->app->bind(ApprovalDecisionPersistence::class, SopApprovalDecisionPersistenceAdapter::class);
        $this->app->bind(ApprovalInstancePersistence::class, SopApprovalPersistenceAdapter::class);
        $this->app->bind(ApprovalSubmissionAuthorization::class, SopApprovalSubmissionAuthorizationAdapter::class);
        $this->app->bind(ApprovalSubmissionLifecycle::class, SopApprovalSubmissionLifecycleAdapter::class);
        $this->app->bind(ApprovalWorkflowDefinitionSelector::class, SopWorkflowDefinitionSelector::class);
        $this->app->singleton(VariableTypeRegistry::class, fn (): VariableTypeRegistry => VariableTypeRegistry::default());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DocumentType::observe(DocumentTypeObserver::class);
    }
}
