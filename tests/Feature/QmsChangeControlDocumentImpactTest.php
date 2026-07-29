<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\DocumentImpactAction;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\ChangeControlDocumentImpact;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\TemplateStatus;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    DocumentStatus::query()->create([
        'code' => DocumentStatus::DRAFT,
        'name' => 'Draft',
    ]);
    TemplateStatus::query()->create([
        'code' => TemplateStatus::DRAFT,
        'name' => 'Draft',
    ]);
});

it('installs controlled document impact traceability', function (): void {
    expect(Schema::hasColumns('change_control_document_impacts', [
        'change_control_id',
        'source_document_id',
        'result_document_id',
        'required_action',
        'rationale',
    ]))->toBeTrue();
});

it('links a required action from a source document to its resulting revision', function (): void {
    $changeControl = ChangeControl::factory()->create();
    $template = DocumentTemplate::factory()->create();
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
    ]);
    $source = ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
    ]);
    $result = ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'document_series_id' => $source->document_series_id,
        'supersedes_document_id' => $source,
        'version' => 2,
    ]);

    $impact = ChangeControlDocumentImpact::factory()->create([
        'change_control_id' => $changeControl,
        'source_document_id' => $source,
        'result_document_id' => $result,
        'required_action' => DocumentImpactAction::Revise,
        'rationale' => 'Update the approved cleaning acceptance criteria.',
    ]);

    expect($impact->required_action)->toBe(DocumentImpactAction::Revise)
        ->and($impact->changeControl->is($changeControl))->toBeTrue()
        ->and($impact->sourceDocument?->is($source))->toBeTrue()
        ->and($impact->resultDocument?->is($result))->toBeTrue()
        ->and($changeControl->documentImpacts()->first()?->is($impact))->toBeTrue();
});

it('prevents duplicate actions for the same change control and source document', function (): void {
    $template = DocumentTemplate::factory()->create();
    $templateVersion = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template,
    ]);
    $source = ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
    ]);
    $impact = ChangeControlDocumentImpact::factory()->create([
        'source_document_id' => $source,
    ]);

    expect(fn () => ChangeControlDocumentImpact::factory()->create([
        'change_control_id' => $impact->change_control_id,
        'source_document_id' => $impact->source_document_id,
        'required_action' => $impact->required_action,
    ]))->toThrow(QueryException::class);
});
