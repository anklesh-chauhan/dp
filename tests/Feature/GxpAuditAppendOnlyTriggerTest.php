<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\BatchReleaseStatus;
use App\Domain\QMS\Enums\ComputerizedSystemIncidentStatus;
use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Models\BatchRelease;
use App\Domain\QMS\Models\ComputerizedSystemIncident;
use App\Domain\QMS\Models\CsvRequirement;
use App\Domain\QMS\Models\CsvSignedDecision;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\QMS\Models\CsvValidationProjectEvent;
use App\Domain\QMS\Models\EquipmentAssetEvent;
use App\Models\DocumentTemplateApprovalEvent;
use App\Models\ProductLicenseAuditEvent;
use App\Models\TemplateStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('blocks raw sql mutation of remaining gxp history tables', function (callable $createEvent): void {
    $event = $createEvent();

    expect($event)->toBeInstanceOf(Model::class)
        ->and(function () use ($event): void {
            DB::transaction(function () use ($event): void {
                DB::table($event->getTable())->where('id', $event->getKey())->delete();
            });
        })
        ->toThrow(QueryException::class, 'append-only');
})->with([
    'equipment asset events' => fn (): Model => EquipmentAssetEvent::factory()->create(),
    'csv validation project events' => fn (): Model => CsvValidationProjectEvent::factory()->create(),
    'csv signed decisions' => function (): Model {
        $project = CsvValidationProject::factory()->create();
        $requirement = CsvRequirement::query()->create([
            'csv_validation_project_id' => $project->id,
            'requirement_identifier' => 'URS-'.Str::upper(Str::random(6)),
            'version' => 1,
            'category' => 'Functional',
            'statement' => 'Dataset trigger check.',
            'acceptance_criteria' => 'Trigger check retained.',
            'criticality' => CsvCriticality::High,
            'gxp_relevant' => true,
            'data_integrity_relevant' => false,
            'status' => CsvRequirementStatus::Draft,
        ]);

        return CsvSignedDecision::factory()->create([
            'csv_validation_project_id' => $project->id,
            'subject_type' => $requirement->getMorphClass(),
            'subject_id' => $requirement->id,
        ]);
    },
    'batch release events' => function (): Model {
        $actor = User::factory()->create();

        return BatchRelease::factory()->create()->auditEvents()->create([
            'event_uuid' => (string) Str::uuid(),
            'from_status' => BatchReleaseStatus::Draft,
            'to_status' => BatchReleaseStatus::UnderReview,
            'actor_id' => $actor->id,
            'reason' => 'Dataset trigger check.',
            'occurred_at' => now(),
        ]);
    },
    'computerized system incident events' => function (): Model {
        $actor = User::factory()->create();

        return ComputerizedSystemIncident::factory()->create()->auditEvents()->create([
            'event_uuid' => (string) Str::uuid(),
            'from_status' => ComputerizedSystemIncidentStatus::Open,
            'to_status' => ComputerizedSystemIncidentStatus::Investigating,
            'actor_id' => $actor->id,
            'reason' => 'Dataset trigger check.',
            'occurred_at' => now(),
        ]);
    },
    'product license audit events' => fn (): Model => ProductLicenseAuditEvent::factory()->create(),
    'document template approval events' => function (): Model {
        TemplateStatus::query()->create([
            'code' => TemplateStatus::DRAFT,
            'name' => 'Draft',
        ]);

        return DocumentTemplateApprovalEvent::factory()->create();
    },
]);
