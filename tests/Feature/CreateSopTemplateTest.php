<?php

declare(strict_types=1);

use App\Filament\Resources\SopTemplates\Pages\CreateSopTemplate;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\RegulationTag;
use App\Models\SopTemplate;
use App\Models\TemplateStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('attaches selected regulation tags when creating a sop template', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $category = DocumentCategory::factory()->create();
    $documentType = DocumentType::factory()->create([
        'category_id' => $category->id,
    ]);

    $tagOne = RegulationTag::factory()->create();
    $tagTwo = RegulationTag::factory()->create();
    $documentType->regulationTags()->attach([$tagOne->id, $tagTwo->id]);

    $department = Department::factory()->create();

    livewire(CreateSopTemplate::class)
        ->fillForm([
            'name' => 'Quarterly QA Template',
            'code' => 'TPL-QA-0001',
            'department_id' => $department->id,
            'description' => 'Template for quarterly QA reviews.',
            'category_id' => $category->id,
            'document_type_id' => $documentType->id,
            'regulationTags' => [$tagOne->id, $tagTwo->id],
            'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
            'current_version' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $template = SopTemplate::query()->latest('id')->first();

    expect($template)->not->toBeNull()
        ->and($template?->regulationTags()->pluck('regulation_tags.id')->all())
        ->toEqualCanonicalizing([$tagOne->id, $tagTwo->id]);
});
