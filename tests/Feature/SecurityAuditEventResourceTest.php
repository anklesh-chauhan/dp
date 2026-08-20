<?php

declare(strict_types=1);

use App\Domain\Shared\Enums\SecurityAuditEventType;
use App\Domain\Shared\Services\SecurityAuditRecorder;
use App\Filament\Resources\SecurityAuditEvents\Pages\ListSecurityAuditEvents;
use App\Filament\Resources\SecurityAuditEvents\Pages\ViewSecurityAuditEvent;
use App\Filament\Resources\SecurityAuditEvents\SecurityAuditEventResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::findOrCreate('ViewAny:SecurityAuditEvent', 'web');
    Permission::findOrCreate('View:SecurityAuditEvent', 'web');

    $this->actingAs(
        User::factory()->create()->givePermissionTo([
            'ViewAny:SecurityAuditEvent',
            'View:SecurityAuditEvent',
        ]),
    );
});

it('registers only index and view pages', function (): void {
    expect(SecurityAuditEventResource::getPages())
        ->toHaveKeys(['index', 'view'])
        ->not->toHaveKeys(['create', 'edit']);
});

it('does not expose mutation operations', function (): void {
    $event = app(SecurityAuditRecorder::class)->record(
        type: SecurityAuditEventType::LoginSucceeded,
        actor: User::factory()->create(),
    );

    expect(SecurityAuditEventResource::canCreate())->toBeFalse()
        ->and(SecurityAuditEventResource::canEdit($event))->toBeFalse()
        ->and(SecurityAuditEventResource::canDelete($event))->toBeFalse()
        ->and(SecurityAuditEventResource::canDeleteAny())->toBeFalse();
});

it('requires security audit visibility permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListSecurityAuditEvents::class)
        ->assertForbidden();
});

it('lists recorded security audit events', function (): void {
    $actor = User::factory()->create(['name' => 'QA Reviewer', 'email' => 'qa.reviewer@example.com']);
    $subject = User::factory()->create(['email' => 'locked.user@example.com']);
    $recorder = app(SecurityAuditRecorder::class);

    $login = $recorder->record(
        type: SecurityAuditEventType::LoginSucceeded,
        actor: $actor,
        subject: $actor,
    );
    $lockout = $recorder->record(
        type: SecurityAuditEventType::AccountLocked,
        actor: $actor,
        subject: $subject,
        reason: 'Too many unsuccessful login attempts.',
        context: ['attempts' => 5],
    );

    Livewire::test(ListSecurityAuditEvents::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$login, $lockout])
        ->assertSee('Login Succeeded')
        ->assertSee('Account Locked')
        ->assertSee('locked.user@example.com')
        ->assertActionDoesNotExist('create')
        ->filterTable('event_type', SecurityAuditEventType::AccountLocked->value)
        ->assertCanSeeTableRecords([$lockout])
        ->assertCanNotSeeTableRecords([$login]);
});

it('shows event detail including context without mutation actions', function (): void {
    $actor = User::factory()->create(['name' => 'System Admin']);
    $event = app(SecurityAuditRecorder::class)->record(
        type: SecurityAuditEventType::RoleChanged,
        actor: $actor,
        subject: $actor,
        reason: 'Assigned document controller.',
        context: ['roles' => ['document controller']],
        ipAddress: '203.0.113.10',
        userAgent: 'QualiGxP-Test-Agent',
    );

    Livewire::test(ViewSecurityAuditEvent::class, ['record' => $event->getKey()])
        ->assertSuccessful()
        ->assertSee($event->event_uuid)
        ->assertSee('Role Changed')
        ->assertSee('Assigned document controller.')
        ->assertSee('document controller')
        ->assertSee('203.0.113.10')
        ->assertSee('QualiGxP-Test-Agent')
        ->assertActionDoesNotExist('edit')
        ->assertActionDoesNotExist('delete');
});
