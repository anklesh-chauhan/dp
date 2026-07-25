<?php

namespace App\Providers;

use App\Domain\DMS\Services\SopApprovalPersistenceAdapter;
use App\Domain\DMS\Services\SopWorkflowDefinitionSelector;
use App\Domain\Shared\Contracts\ApprovalInstancePersistence;
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
        $this->app->bind(ApprovalInstancePersistence::class, SopApprovalPersistenceAdapter::class);
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
