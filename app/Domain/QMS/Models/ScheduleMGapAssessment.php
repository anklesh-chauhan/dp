<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Concerns\ProvidesNarrativeSignatureContent;
use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Policies\ScheduleMGapAssessmentPolicy;
use App\Domain\Shared\Contracts\ProvidesElectronicSignatureContent;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\ScheduleMGapAssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

#[UsePolicy(ScheduleMGapAssessmentPolicy::class)]
final class ScheduleMGapAssessment extends Model implements ProvidesElectronicSignatureContent
{
    /** @use HasFactory<ScheduleMGapAssessmentFactory> */
    use HasFactory;

    use ProvidesNarrativeSignatureContent;

    protected $fillable = [
        'assessment_number',
        'title',
        'status',
        'site_name',
        'period_label',
        'owner_id',
        'created_by',
        'approved_at',
        'closed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $assessment): void {
            $assessment->assessment_number ??= sprintf(
                'SMG-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /**
     * Key Schedule M Part I topics used to seed clause checklists (not gazette text).
     *
     * @return list<array{part_code: string, clause_ref: string, clause_title: string}>
     */
    public static function partIClauseDefinitions(): array
    {
        return [
            ['part_code' => 'Part_I', 'clause_ref' => 'I.1', 'clause_title' => 'Pharmaceutical Quality System (PQS)'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.2', 'clause_title' => 'Quality Risk Management (QRM)'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.3', 'clause_title' => 'Product Quality Review (PQR)'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.4', 'clause_title' => 'Change Control'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.5', 'clause_title' => 'Deviation Management'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.6', 'clause_title' => 'Corrective and Preventive Action (CAPA)'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.7', 'clause_title' => 'Validation and Qualification'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.8', 'clause_title' => 'Self-inspection / Internal Audit'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.9', 'clause_title' => 'Complaints Handling'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.10', 'clause_title' => 'Product Recalls'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.11', 'clause_title' => 'Documentation and Document Control'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.12', 'clause_title' => 'Computerized Systems'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.13', 'clause_title' => 'Personnel and Training'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.14', 'clause_title' => 'Premises and Equipment'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.15', 'clause_title' => 'Materials Management'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.16', 'clause_title' => 'Production Operations'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.17', 'clause_title' => 'Quality Control Laboratory'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.18', 'clause_title' => 'Contract Manufacture and Analysis'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.19', 'clause_title' => 'Batch Release'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.20', 'clause_title' => 'Ongoing Stability Programme'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.21', 'clause_title' => 'Retention Samples'],
            ['part_code' => 'Part_I', 'clause_ref' => 'I.22', 'clause_title' => 'Returns and Reprocessing'],
        ];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ScheduleMGapAssessmentStatus::class,
            'approved_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<ScheduleMGapItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ScheduleMGapItem::class, 'assessment_id');
    }

    /** @return HasMany<ScheduleMGapAssessmentEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(ScheduleMGapAssessmentEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
