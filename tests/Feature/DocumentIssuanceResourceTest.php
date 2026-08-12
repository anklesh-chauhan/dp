<?php

declare(strict_types=1);

use App\Filament\Resources\DocumentIssuances\Pages\ListDocumentIssuances;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentIssuance;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\TemplateStatus;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms']);
    $this->seed(LookupTableSeeder::class);

    foreach (['ViewAny:DocumentIssuance', 'View:DocumentIssuance'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['ViewAny:DocumentIssuance', 'View:DocumentIssuance']);
    $this->actingAs($this->user);
});

it('groups issuance register row actions behind an overflow menu', function (): void {
    $department = Department::factory()->create();
    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::query()->where('code', DocumentType::BATCH_RECORD)->firstOrFail();
    $template = DocumentTemplate::factory()->create([
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
    ]);
    $templateVersion = DocumentTemplateVersion::factory()->create(['document_template_id' => $template]);
    $document = ControlledDocument::factory()->create([
        'template_id' => $template,
        'template_version_id' => $templateVersion,
        'department_id' => $department,
        'category_id' => $category,
        'document_type_id' => $documentType,
        'document_status_id' => DocumentStatus::idFor(DocumentStatus::EFFECTIVE),
    ]);
    $issuance = DocumentIssuance::factory()->create([
        'document_id' => $document,
        'issued_to_user_id' => $this->user->id,
        'issuance_type' => DocumentIssuance::TYPE_EXECUTION,
    ]);

    Livewire::test(ListDocumentIssuances::class)
        ->assertCanSeeTableRecords([$issuance])
        ->assertActionExists(TestAction::make('viewDocument')->table($issuance))
        ->assertActionExists(TestAction::make('printCopy')->table($issuance));
});
