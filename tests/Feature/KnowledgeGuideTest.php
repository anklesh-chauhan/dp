<?php

declare(strict_types=1);

use App\Filament\Resources\KnowledgeGuides\Pages\ListKnowledgeGuides;
use App\Filament\Resources\KnowledgeGuides\Pages\ViewKnowledgeGuide;
use App\Models\KnowledgeGuide;
use App\Models\User;
use Database\Seeders\KnowledgeGuideSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach ([
        'ViewAny:KnowledgeGuide',
        'View:KnowledgeGuide',
        'Create:KnowledgeGuide',
        'Update:KnowledgeGuide',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
});

it('seeds the document classification guide from USER_GUIDE.md', function (): void {
    $this->seed(KnowledgeGuideSeeder::class);

    $guide = KnowledgeGuide::query()->where('slug', 'document-classification')->first();

    expect($guide)->not->toBeNull()
        ->and($guide?->title)->toBe('Document Categorization & Type Selection in QMS')
        ->and($guide?->is_published)->toBeTrue()
        ->and($guide?->content)->toContain('Quick-Reference Cross-Mapping Matrix')
        ->and($guide?->renderedHtml())->toContain('<table');
});

it('lists published guides for users with view permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(['ViewAny:KnowledgeGuide', 'View:KnowledgeGuide']);

    $published = KnowledgeGuide::factory()->create(['title' => 'Published Guide']);
    KnowledgeGuide::factory()->unpublished()->create(['title' => 'Draft Guide']);

    actingAs($user);

    livewire(ListKnowledgeGuides::class)
        ->assertCanSeeTableRecords([$published])
        ->assertCanNotSeeTableRecords(KnowledgeGuide::query()->where('is_published', false)->get());
});

it('renders markdown content on the guide view page', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(['ViewAny:KnowledgeGuide', 'View:KnowledgeGuide']);

    $guide = KnowledgeGuide::factory()->create([
        'title' => 'Markdown Guide',
        'content' => "# Heading\n\nParagraph with **bold** text.",
    ]);

    actingAs($user);

    livewire(ViewKnowledgeGuide::class, ['record' => $guide->id])
        ->assertSuccessful()
        ->assertSee('Markdown Guide');
});
