<?php

declare(strict_types=1);

use App\Filament\Resources\DocumentTemplates\Pages\EditDocumentTemplate;
use App\Filament\Resources\DocumentTemplates\Pages\ViewDocumentTemplate;
use App\Filament\Resources\DocumentTemplates\RelationManagers\SectionRelationManager;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\User;
use Database\Seeders\LookupTableSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(LookupTableSeeder::class);
});

it('hides AI section title generation on the template view page', function (): void {
    config(['modules.enabled' => ['dms', 'ai']]);
    Gate::before(static fn (): bool => true);

    $user = User::factory()->create();
    $template = DocumentTemplate::factory()->create([
        'created_by' => $user->getKey(),
        'document_type_id' => DocumentType::query()->firstOrFail(),
    ]);

    $this->actingAs($user);

    Livewire::test(SectionRelationManager::class, [
        'ownerRecord' => $template,
        'pageClass' => ViewDocumentTemplate::class,
    ])->assertActionHidden(TestAction::make('generateSectionTitlesWithAi')->table());
});

it('shows AI section title generation on the editable template page', function (): void {
    config(['modules.enabled' => ['dms', 'ai']]);
    Gate::before(static fn (): bool => true);

    $user = User::factory()->create();
    $template = DocumentTemplate::factory()->create([
        'created_by' => $user->getKey(),
        'document_type_id' => DocumentType::query()->firstOrFail(),
    ]);

    $this->actingAs($user);

    Livewire::test(SectionRelationManager::class, [
        'ownerRecord' => $template,
        'pageClass' => EditDocumentTemplate::class,
    ])->assertActionVisible(TestAction::make('generateSectionTitlesWithAi')->table());
});
