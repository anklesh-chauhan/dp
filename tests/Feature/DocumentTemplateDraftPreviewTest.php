<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateSection;
use App\Models\DocumentTemplateVariable;
use App\Models\DocumentTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('renders the latest draft template version in the draft preview', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $draftStatus = TemplateStatus::factory()->create([
        'name' => 'Draft',
        'code' => TemplateStatus::DRAFT,
    ]);
    $template = DocumentTemplate::factory()->create([
        'created_by' => $user->getKey(),
        'department_id' => Department::factory(),
        'template_status_id' => $draftStatus->getKey(),
    ]);
    $version = DocumentTemplateVersion::factory()->create([
        'document_template_id' => $template->getKey(),
        'template_status_id' => $draftStatus->getKey(),
        'version' => 1,
    ]);

    DocumentTemplateSection::factory()->create([
        'template_version_id' => $version->getKey(),
        'title' => 'Responsibilities',
        'content' => '<p>Follow this procedure.</p>',
    ]);
    DocumentTemplateVariable::factory()->create([
        'template_version_id' => $version->getKey(),
        'name' => 'owner_name',
        'label' => 'Owner',
        'default_value' => 'Quality Assurance',
    ]);

    get(route('document-templates.draft-preview', $template))
        ->assertOk()
        ->assertSee('Draft Preview')
        ->assertSee('Responsibilities')
        ->assertSee('Follow this procedure.', false)
        ->assertSee('Quality Assurance');
});
