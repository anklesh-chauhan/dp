<?php

namespace App\Providers;

use App\Domain\DMS\Contracts\ControlledDocumentPdfRenderer;
use App\Domain\DMS\Services\GotenbergControlledDocumentPdfRenderer;
use App\Domain\DMS\Services\SopApprovalDecisionAuthorizationAdapter;
use App\Domain\DMS\Services\SopApprovalDecisionOutcomeAdapter;
use App\Domain\DMS\Services\SopApprovalDecisionPersistenceAdapter;
use App\Domain\DMS\Services\SopApprovalPersistenceAdapter;
use App\Domain\DMS\Services\SopApprovalSubmissionAuthorizationAdapter;
use App\Domain\DMS\Services\SopApprovalSubmissionLifecycleAdapter;
use App\Domain\DMS\Services\SopWorkflowDefinitionSelector;
use App\Domain\QMS\Services\DeviationApprovalDecisionService;
use App\Domain\QMS\Services\QualityApprovalDecisionAuthorization;
use App\Domain\QMS\Services\QualityApprovalDecisionOutcome;
use App\Domain\QMS\Services\QualityApprovalDecisionPersistence;
use App\Domain\Shared\Contracts\ApprovalDecisionAuthorization;
use App\Domain\Shared\Contracts\ApprovalDecisionOutcome;
use App\Domain\Shared\Contracts\ApprovalDecisionPersistence;
use App\Domain\Shared\Contracts\ApprovalInstancePersistence;
use App\Domain\Shared\Contracts\ApprovalSubmissionAuthorization;
use App\Domain\Shared\Contracts\ApprovalSubmissionLifecycle;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinitionSelector;
use App\Domain\Shared\Contracts\ContentIntegrityHasher;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Domain\Shared\Services\CanonicalElectronicSignatureVerifier;
use App\Domain\Shared\Services\Sha256ContentIntegrityHasher;
use App\Domain\Shared\Services\Sha256ElectronicSignatureHasher;
use App\Models\DocumentType;
use App\Observers\DocumentTypeObserver;
use App\Support\Formatting\DateFormatSettings;
use App\Support\Modules\AuditedProductLicenseRevoker;
use App\Support\Modules\ConfiguredModuleEntitlementProvider;
use App\Support\Modules\Contracts\LicenseAuditRecorder;
use App\Support\Modules\Contracts\LicenseLifecycleEvaluator;
use App\Support\Modules\Contracts\ModuleEntitlementProvider;
use App\Support\Modules\Contracts\ProductLicenseRevoker;
use App\Support\Modules\Contracts\ProductLicenseStateResolver;
use App\Support\Modules\Contracts\SignedLicenseActivator;
use App\Support\Modules\Contracts\SignedLicenseVerifier;
use App\Support\Modules\EloquentLicenseAuditRecorder;
use App\Support\Modules\OpenSslSignedLicenseVerifier;
use App\Support\Modules\SignedLicenseEntitlementProvider;
use App\Support\Modules\ValidatedSignedLicenseActivator;
use App\Support\Modules\VerifiedLicenseLifecycleEvaluator;
use App\Support\Modules\VerifiedProductLicenseStateResolver;
use App\Support\Sop\VariableTypes\VariableTypeRegistry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ControlledDocumentPdfRenderer::class, GotenbergControlledDocumentPdfRenderer::class);
        $this->app->bind(ApprovalDecisionAuthorization::class, SopApprovalDecisionAuthorizationAdapter::class);
        $this->app->bind(ApprovalDecisionOutcome::class, SopApprovalDecisionOutcomeAdapter::class);
        $this->app->bind(ApprovalDecisionPersistence::class, SopApprovalDecisionPersistenceAdapter::class);
        $this->app->bind(ApprovalInstancePersistence::class, SopApprovalPersistenceAdapter::class);
        $this->app->bind(ApprovalSubmissionAuthorization::class, SopApprovalSubmissionAuthorizationAdapter::class);
        $this->app->bind(ApprovalSubmissionLifecycle::class, SopApprovalSubmissionLifecycleAdapter::class);
        $this->app->bind(ApprovalWorkflowDefinitionSelector::class, SopWorkflowDefinitionSelector::class);
        $this->app->when(DeviationApprovalDecisionService::class)
            ->needs(ApprovalDecisionAuthorization::class)
            ->give(QualityApprovalDecisionAuthorization::class);
        $this->app->when(DeviationApprovalDecisionService::class)
            ->needs(ApprovalDecisionOutcome::class)
            ->give(QualityApprovalDecisionOutcome::class);
        $this->app->when(DeviationApprovalDecisionService::class)
            ->needs(ApprovalDecisionPersistence::class)
            ->give(QualityApprovalDecisionPersistence::class);
        $this->app->bind(ElectronicSignatureHasher::class, Sha256ElectronicSignatureHasher::class);
        $this->app->bind(ElectronicSignatureVerifier::class, CanonicalElectronicSignatureVerifier::class);
        $this->app->bind(ContentIntegrityHasher::class, Sha256ContentIntegrityHasher::class);
        $this->app->bind(SignedLicenseVerifier::class, OpenSslSignedLicenseVerifier::class);
        $this->app->bind(SignedLicenseActivator::class, ValidatedSignedLicenseActivator::class);
        $this->app->bind(LicenseLifecycleEvaluator::class, VerifiedLicenseLifecycleEvaluator::class);
        $this->app->bind(ProductLicenseStateResolver::class, VerifiedProductLicenseStateResolver::class);
        $this->app->bind(LicenseAuditRecorder::class, EloquentLicenseAuditRecorder::class);
        $this->app->bind(ProductLicenseRevoker::class, AuditedProductLicenseRevoker::class);
        $this->app->bind(
            ModuleEntitlementProvider::class,
            fn (Application $app): ModuleEntitlementProvider => match (
                strtolower(trim((string) $app->make('config')->get('modules.entitlement_source', 'environment')))
            ) {
                'environment' => $app->make(ConfiguredModuleEntitlementProvider::class),
                'signed_license' => $app->make(SignedLicenseEntitlementProvider::class),
                default => throw new InvalidArgumentException(
                    'Unknown product-module entitlement source.',
                ),
            },
        );
        $this->app->singleton(VariableTypeRegistry::class, fn (): VariableTypeRegistry => VariableTypeRegistry::default());
        $this->app->singleton(DateFormatSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DocumentType::observe(DocumentTypeObserver::class);

        $this->configureFilamentDateFormats();
    }

    private function configureFilamentDateFormats(): void
    {
        Table::configureUsing(function (Table $table): void {
            $formats = app(DateFormatSettings::class);

            $table
                ->defaultDateDisplayFormat(fn (): string => $formats->date())
                ->defaultDateTimeDisplayFormat(fn (): string => $formats->dateTime())
                ->defaultTimeDisplayFormat(fn (): string => $formats->time());
        });

        Schema::configureUsing(function (Schema $schema): void {
            $formats = app(DateFormatSettings::class);

            $schema
                ->defaultDateDisplayFormat(fn (): string => $formats->date())
                ->defaultDateTimeDisplayFormat(fn (): string => $formats->dateTime())
                ->defaultTimeDisplayFormat(fn (): string => $formats->time());
        });

        DatePicker::configureUsing(function (DatePicker $datePicker): void {
            $formats = app(DateFormatSettings::class);

            $datePicker->defaultDateDisplayFormat(fn (): string => $formats->date());
        });

        DateTimePicker::configureUsing(function (DateTimePicker $dateTimePicker): void {
            $formats = app(DateFormatSettings::class);

            $dateTimePicker
                ->defaultDateDisplayFormat(fn (): string => $formats->date())
                ->defaultDateTimeDisplayFormat(fn (): string => $formats->dateTime())
                ->defaultTimeDisplayFormat(fn (): string => $formats->time());
        });
    }
}
