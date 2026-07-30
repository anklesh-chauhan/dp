<?php

declare(strict_types=1);

use App\Domain\DMS\Services\DocumentIssuanceService;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentIssuance;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\IssuanceStatus;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
    $this->issuer = User::factory()->create();
});

function issuableControlledDocument(string $documentNumber = 'DEV-QA-00001'): ControlledDocument
{
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::query()->where('code', 'DEV')->firstOrFail();
    $documentType->update([
        'is_issuable' => true,
        'requires_sop_reference' => false,
    ]);
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
    ]);

    return ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
        'document_number' => $documentNumber,
    ]);
}

it('issues sequential unique controlled-copy numbers', function (): void {
    $document = issuableControlledDocument();
    $service = app(DocumentIssuanceService::class);

    $first = $service->issue($document, $this->issuer);
    $second = $service->issue($document, $this->issuer);

    expect($first->copy_number)->toBe(1)
        ->and($first->issuance_number)->toBe('DEV-QA-00001-C01')
        ->and($second->copy_number)->toBe(2)
        ->and($second->issuance_number)->toBe('DEV-QA-00001-C02')
        ->and(DocumentIssuance::query()->distinct()->count('issuance_number'))->toBe(2);
});

it('skips an already reserved issuance number instead of raising a unique constraint error', function (): void {
    $document = issuableControlledDocument();

    DocumentIssuance::query()->create([
        'document_id' => $document->id,
        'copy_number' => 1,
        'issuance_number' => 'DEV-QA-00001-C01',
        'issued_by' => $this->issuer->id,
        'issued_at' => now()->subMinute(),
        'issuance_status_id' => IssuanceStatus::idFor(IssuanceStatus::ACTIVE),
        'watermark_code' => 'CC-DEVQA00001-01',
    ]);

    $issuance = app(DocumentIssuanceService::class)->issue($document, $this->issuer);

    expect($issuance->copy_number)->toBe(2)
        ->and($issuance->issuance_number)->toBe('DEV-QA-00001-C02')
        ->and($issuance->watermark_code)->toBe('CC-DEVQA00001-02');
});
