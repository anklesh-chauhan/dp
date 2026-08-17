<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Models\ScheduleMGapItem;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Database\Seeders\ScheduleMGapAssessmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the schedule m gap assessment schema', function (): void {
    expect(Schema::hasColumns('schedule_m_gap_assessments', [
        'assessment_number',
        'title',
        'status',
        'site_name',
        'period_label',
        'owner_id',
        'created_by',
        'approved_at',
        'closed_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('schedule_m_gap_items', [
            'assessment_id',
            'part_code',
            'clause_ref',
            'clause_title',
            'status',
            'evidence_notes',
            'evidence_document_id',
            'evidence_qms_type',
            'evidence_qms_id',
            'owner_id',
            'due_at',
            'closed_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('schedule_m_gap_assessment_events', [
            'event_uuid',
            'schedule_m_gap_assessment_id',
            'from_status',
            'to_status',
            'event_type',
            'actor_id',
            'reason',
            'context',
            'signature_hash',
            'signature_ip_address',
            'signature_user_agent',
            'occurred_at',
        ]))->toBeTrue();
});

it('persists assessment ownership and seeds the part i checklist', function (): void {
    $owner = User::factory()->create();
    $creator = User::factory()->create();

    $assessment = ScheduleMGapAssessment::factory()->create([
        'title' => 'FY2026 Schedule M Gap Assessment',
        'site_name' => 'Plant A',
        'period_label' => 'CY2026',
        'owner_id' => $owner,
        'created_by' => $creator,
        'status' => ScheduleMGapAssessmentStatus::Draft,
    ]);

    app(ScheduleMGapAssessmentSeeder::class)->seedPartIChecklist($assessment);

    expect($assessment->assessment_number)->toStartWith('SMG-')
        ->and($assessment->items()->count())->toBe(count(ScheduleMGapAssessment::partIClauseDefinitions()))
        ->and($assessment->items()->where('clause_ref', 'I.1')->value('clause_title'))
        ->toBe('Pharmaceutical Quality System (PQS)')
        ->and($assessment->items()->where('status', ScheduleMGapItemStatus::NotAssessed->value)->count())
        ->toBe(count(ScheduleMGapAssessment::partIClauseDefinitions()));
});

it('supports the part i checklist factory state', function (): void {
    $assessment = ScheduleMGapAssessment::factory()->withPartIChecklist()->create();

    expect($assessment->items)->toHaveCount(count(ScheduleMGapAssessment::partIClauseDefinitions()))
        ->and(ScheduleMGapItem::query()->where('assessment_id', $assessment->id)->exists())->toBeTrue();
});

it('registers schedule m gap assessment permissions in the qms module seeder', function (): void {
    $this->seed(QmsModuleSeeder::class);

    expect(QmsModuleSeeder::PERMISSIONS)->toContain('ViewAny:ScheduleMGapAssessment')
        ->and(QmsModuleSeeder::PERMISSIONS)->toContain('Export:InspectorEvidencePack');
});
