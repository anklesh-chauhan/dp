<?php

declare(strict_types=1);

use App\Domain\Shared\Services\AuditLogService;
use App\Models\DocumentStatus;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves canonical and legacy audit services through the Shared boundary', function (): void {
    expect(app(AuditLogService::class))->toBeInstanceOf(AuditLogService::class)
        ->and(app(App\Services\Sop\AuditLogService::class))->toBeInstanceOf(AuditLogService::class);
});

it('preserves attributable SOP audit persistence', function (): void {
    DocumentStatus::query()->create([
        'code' => DocumentStatus::DRAFT,
        'name' => 'Draft',
    ]);
    TemplateStatus::query()->create([
        'code' => TemplateStatus::DRAFT,
        'name' => 'Draft',
    ]);

    $user = User::factory()->create();
    $template = SopTemplate::factory()->create();
    $templateVersion = SopTemplateVersion::factory()->create([
        'sop_template_id' => $template->id,
    ]);
    $document = SopDocument::factory()->create([
        'template_id' => $template->id,
        'template_version_id' => $templateVersion->id,
    ]);

    $this->actingAs($user);

    $auditLog = app(AuditLogService::class)->log(
        action: SopAuditLog::ACTION_UPDATED,
        oldValues: ['title' => 'Old title'],
        newValues: ['title' => 'New title'],
        document: $document,
    );

    expect($auditLog->document_id)->toBe($document->id)
        ->and($auditLog->sop_template_id)->toBe($document->template_id)
        ->and($auditLog->user_id)->toBe($user->id)
        ->and($auditLog->action)->toBe(SopAuditLog::ACTION_UPDATED)
        ->and($auditLog->old_values)->toBe(['title' => 'Old title'])
        ->and($auditLog->new_values)->toBe(['title' => 'New title']);
});

arch('Shared services are classes')
    ->expect('App\Domain\Shared\Services')
    ->toBeClasses();

arch('Shared domain code does not depend on optional modules')
    ->expect('App\Domain\Shared')
    ->not->toUse([
        'App\Domain\AI',
        'App\Domain\QMS',
        'App\Foundation\AI',
        'App\Services\AI',
    ]);
