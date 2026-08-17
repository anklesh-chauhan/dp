<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\Investigation;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\RiskAssessmentLink;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class RiskAssessmentLinkService
{
    /** @var list<class-string<Model>> */
    private const array LINKABLE_TYPES = [
        ChangeControl::class,
        Deviation::class,
        Capa::class,
        Complaint::class,
        AuditFinding::class,
        CsvValidationProject::class,
        Investigation::class,
    ];

    public function __construct(private readonly ModuleManager $moduleManager) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function attach(
        RiskAssessment $riskAssessment,
        Model $linkable,
        User $actor,
        ?string $linkRole = null,
    ): RiskAssessmentLink {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);
        $this->assertCanLink($actor);
        $this->assertLinkable($linkable);

        $alreadyLinked = RiskAssessmentLink::query()
            ->where('risk_assessment_id', $riskAssessment->getKey())
            ->where('linkable_type', $linkable->getMorphClass())
            ->where('linkable_id', $linkable->getKey())
            ->exists();

        if ($alreadyLinked) {
            throw ValidationException::withMessages([
                'linkable' => 'This quality record is already linked to the risk assessment.',
            ]);
        }

        /** @var RiskAssessmentLink $link */
        $link = $riskAssessment->links()->create([
            'linkable_type' => $linkable->getMorphClass(),
            'linkable_id' => $linkable->getKey(),
            'link_role' => filled($linkRole) ? trim((string) $linkRole) : null,
        ]);

        return $link;
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function attachToEvent(
        Model $linkable,
        RiskAssessment $riskAssessment,
        User $actor,
        ?string $linkRole = null,
    ): RiskAssessmentLink {
        return $this->attach($riskAssessment, $linkable, $actor, $linkRole);
    }

    /**
     * @throws AuthorizationException
     */
    public function detach(RiskAssessmentLink $link, User $actor): void
    {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);
        $this->assertCanLink($actor);

        $link->delete();
    }

    /**
     * Sync discrete optional foreign-key style selections onto morph links.
     *
     * @param  array{
     *     change_control_id?: int|null,
     *     deviation_id?: int|null,
     *     capa_id?: int|null,
     *     complaint_id?: int|null,
     *     audit_finding_id?: int|null,
     *     csv_validation_project_id?: int|null,
     *     investigation_id?: int|null,
     * }  $selections
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function syncSelections(
        RiskAssessment $riskAssessment,
        array $selections,
        User $actor,
        ?string $defaultRole = 'source',
    ): void {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);
        $this->assertCanLink($actor);

        /** @var array<string, class-string<Model>> $map */
        $map = [
            'change_control_id' => ChangeControl::class,
            'deviation_id' => Deviation::class,
            'capa_id' => Capa::class,
            'complaint_id' => Complaint::class,
            'audit_finding_id' => AuditFinding::class,
            'csv_validation_project_id' => CsvValidationProject::class,
            'investigation_id' => Investigation::class,
        ];

        foreach ($map as $key => $type) {
            if (! array_key_exists($key, $selections)) {
                continue;
            }

            $id = $selections[$key];

            RiskAssessmentLink::query()
                ->where('risk_assessment_id', $riskAssessment->getKey())
                ->where('linkable_type', (new $type)->getMorphClass())
                ->delete();

            if ($id === null || $id === '') {
                continue;
            }

            /** @var Model $record */
            $record = $type::query()->findOrFail((int) $id);
            $this->assertLinkable($record);

            $riskAssessment->links()->create([
                'linkable_type' => $record->getMorphClass(),
                'linkable_id' => $record->getKey(),
                'link_role' => $defaultRole,
            ]);
        }
    }

    private function assertCanLink(User $actor): void
    {
        if (! $actor->can('Update:RiskAssessment')) {
            throw new AuthorizationException(
                'You do not have permission to manage risk assessment event links.',
            );
        }
    }

    private function assertLinkable(Model $linkable): void
    {
        if (! in_array($linkable::class, self::LINKABLE_TYPES, true)) {
            throw ValidationException::withMessages([
                'linkable' => 'This record type cannot be linked to a risk assessment.',
            ]);
        }
    }
}
