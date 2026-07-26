<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\AttachmentIntegrityStatus;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\InternalAudit;
use App\Domain\QMS\Models\Investigation;
use App\Domain\QMS\Models\ManagementReview;
use App\Domain\QMS\Models\QualityAttachment;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\SupplierQualification;
use App\Domain\QMS\Services\QualityAttachmentIntegrityService;
use App\Filament\Resources\Deviations\Pages\ViewDeviation;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    Storage::fake('local');
    foreach (['View:Deviation', 'View:QualityAttachment', 'Create:QualityAttachment'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->user = User::factory()->create();
    $this->user->givePermissionTo([
        'View:Deviation',
        'View:QualityAttachment',
        'Create:QualityAttachment',
    ]);
    $this->actingAs($this->user);
});

it('stores private attributable immutable evidence from a quality record', function (): void {
    $deviation = Deviation::factory()->create();
    $contents = str_repeat('quality-evidence', 128);

    Livewire::test(QualityAttachmentsRelationManager::class, [
        'ownerRecord' => $deviation,
        'pageClass' => ViewDeviation::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'path' => UploadedFile::fake()->createWithContent(
                'temperature-report.pdf',
                $contents,
            ),
            'description' => 'Calibrated logger export for the excursion.',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $attachment = QualityAttachment::query()->sole();

    expect($attachment->attachable->is($deviation))->toBeTrue()
        ->and($attachment->original_name)->toBe('temperature-report.pdf')
        ->and($attachment->disk)->toBe('local')
        ->and($attachment->uploaded_by)->toBe($this->user->id)
        ->and($attachment->attachment_uuid)->not->toBeEmpty()
        ->and($attachment->size_bytes)->toBeGreaterThan(0)
        ->and($attachment->content_hash)->toBe(hash('sha256', $contents))
        ->and(app(QualityAttachmentIntegrityService::class)->status($attachment))
        ->toBe(AttachmentIntegrityStatus::Verified)
        ->and(Storage::disk('local')->exists($attachment->path))->toBeTrue();

    expect(fn () => $attachment->update(['description' => 'tampered']))
        ->toThrow(LogicException::class);
    expect(fn () => $attachment->delete())
        ->toThrow(LogicException::class);
});

it('detects altered missing and legacy-unhashed evidence', function (): void {
    $contents = 'original attributable evidence';
    Storage::disk('local')->put('qms/quality-attachments/integrity.txt', $contents);
    $attachment = Deviation::factory()->create()->attachments()->create([
        'path' => 'qms/quality-attachments/integrity.txt',
        'original_name' => 'integrity.txt',
        'uploaded_by' => $this->user->id,
    ]);
    $integrity = app(QualityAttachmentIntegrityService::class);

    expect($integrity->status($attachment))->toBe(AttachmentIntegrityStatus::Verified);

    Storage::disk('local')->put($attachment->path, 'altered evidence');

    expect($integrity->status($attachment))->toBe(AttachmentIntegrityStatus::Tampered);

    Storage::disk('local')->delete($attachment->path);

    expect($integrity->status($attachment))->toBe(AttachmentIntegrityStatus::Missing);

    $legacyAttachment = QualityAttachment::factory()->create([
        'path' => 'qms/quality-attachments/legacy-missing.txt',
        'content_hash' => null,
    ]);

    expect($integrity->status($legacyAttachment))->toBe(AttachmentIntegrityStatus::Unverified);
});

it('shares the attachment boundary across complaint deviation investigation CAPA audit risk supplier and review records', function (): void {
    $complaint = Complaint::factory()->create();
    $deviation = Deviation::factory()->create();
    $investigation = Investigation::factory()->create(['deviation_id' => $deviation]);
    $capa = Capa::factory()->create([
        'deviation_id' => $deviation,
        'investigation_id' => $investigation,
    ]);
    $audit = InternalAudit::factory()->create();
    $finding = AuditFinding::factory()->create(['internal_audit_id' => $audit]);
    $riskAssessment = RiskAssessment::factory()->create();
    $supplierQualification = SupplierQualification::factory()->create();
    $managementReview = ManagementReview::factory()->create();

    foreach ([
        $complaint,
        $deviation,
        $investigation,
        $capa,
        $audit,
        $finding,
        $riskAssessment,
        $supplierQualification,
        $managementReview,
    ] as $record) {
        $record->attachments()->create([
            'path' => fake()->uuid().'.pdf',
            'original_name' => 'evidence.pdf',
            'uploaded_by' => $this->user->id,
        ]);
    }

    expect($complaint->attachments)->toHaveCount(1)
        ->and($deviation->attachments)->toHaveCount(1)
        ->and($investigation->attachments)->toHaveCount(1)
        ->and($capa->attachments)->toHaveCount(1)
        ->and($audit->attachments)->toHaveCount(1)
        ->and($finding->attachments)->toHaveCount(1)
        ->and($riskAssessment->attachments)->toHaveCount(1)
        ->and($supplierQualification->attachments)->toHaveCount(1)
        ->and($managementReview->attachments)->toHaveCount(1)
        ->and(QualityAttachment::query()->count())->toBe(9);
});

it('denies attachment visibility when QMS is disabled or permission is absent', function (): void {
    $deviation = Deviation::factory()->create();

    expect(QualityAttachmentsRelationManager::canViewForRecord(
        $deviation,
        ViewDeviation::class,
    ))->toBeTrue();

    config()->set('modules.enabled', ['dms']);

    expect(QualityAttachmentsRelationManager::canViewForRecord(
        $deviation,
        ViewDeviation::class,
    ))->toBeFalse();

    config()->set('modules.enabled', ['dms', 'qms']);
    $this->actingAs(User::factory()->create());

    expect(QualityAttachmentsRelationManager::canViewForRecord(
        $deviation,
        ViewDeviation::class,
    ))->toBeFalse();
});
