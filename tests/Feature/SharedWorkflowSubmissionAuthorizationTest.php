<?php

declare(strict_types=1);

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Models\User;
use App\Services\Sop\WorkflowEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('authorizes submission through the Shared approvable subject contract', function (): void {
    Permission::findOrCreate('Submit:SopDocument', 'web');

    $owner = User::factory()->create();
    $owner->givePermissionTo('Submit:SopDocument');

    $otherUser = User::factory()->create();
    $otherUser->givePermissionTo('Submit:SopDocument');

    $subject = new class($owner->id) implements ApprovableSubject
    {
        public function __construct(private readonly int $ownerId) {}

        public function approvalSubjectKey(): int|string|null
        {
            return 'quality-event-42';
        }

        public function approvalSubjectReference(): string
        {
            return 'QMS-DEV-00042';
        }

        public function approvalSubjectTitle(): string
        {
            return 'Temperature excursion';
        }

        public function approvalSubjectDepartmentId(): ?int
        {
            return null;
        }

        public function approvalSubjectCreatedById(): ?int
        {
            return null;
        }

        public function approvalSubjectOwnerId(): ?int
        {
            return $this->ownerId;
        }
    };

    $workflow = app(WorkflowEngineService::class);

    expect($workflow->canSubmit($subject, $owner))->toBeTrue()
        ->and($workflow->canSubmit($subject, $otherUser))->toBeFalse();
});

it('rejects a Shared approvable subject when the user lacks submission permission', function (): void {
    $subject = new class implements ApprovableSubject
    {
        public function approvalSubjectKey(): int|string|null
        {
            return 'quality-event-43';
        }

        public function approvalSubjectReference(): string
        {
            return 'QMS-DEV-00043';
        }

        public function approvalSubjectTitle(): string
        {
            return 'Unapproved deviation';
        }

        public function approvalSubjectDepartmentId(): ?int
        {
            return null;
        }

        public function approvalSubjectCreatedById(): ?int
        {
            return null;
        }

        public function approvalSubjectOwnerId(): ?int
        {
            return null;
        }
    };

    expect(app(WorkflowEngineService::class)->canSubmit(
        $subject,
        User::factory()->create(),
    ))->toBeFalse();
});
