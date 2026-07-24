<?php

declare(strict_types=1);

use App\Filament\Resources\AiExecutions\AiExecutionResource;
use App\Filament\Resources\AiExecutions\Pages\ViewAiExecution;
use App\Filament\Resources\AiExecutions\RelationManagers\AttemptsRelationManager;
use App\Models\AiExecution;
use App\Models\AiExecutionAttempt;
use App\Services\AI\Enums\AiExecutionStatus;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('registers only index and view pages', function (): void {
    expect(AiExecutionResource::getPages())
        ->toHaveKeys([
            'index',
            'view',
        ])
        ->not->toHaveKeys([
            'create',
            'edit',
        ]);
});

it('registers the attempts relation manager', function (): void {
    expect(AiExecutionResource::getRelations())
        ->toBe([
            AttemptsRelationManager::class,
        ]);
});

it('does not allow creating executions through the resource', function (): void {
    expect(AiExecutionResource::canCreate())
        ->toBeFalse();
});

it('does not allow editing executions through the resource', function (): void {
    $execution = AiExecution::factory()->create();

    expect(AiExecutionResource::canEdit($execution))
        ->toBeFalse();
});

it('does not allow deleting executions through the resource', function (): void {
    $execution = AiExecution::factory()->create();

    expect(AiExecutionResource::canDelete($execution))
        ->toBeFalse()
        ->and(AiExecutionResource::canDeleteAny())
        ->toBeFalse();
});

it('renders execution attempts on the view page', function (): void {
    $execution = AiExecution::factory()->create();

    $attempts = AiExecutionAttempt::factory()
        ->count(2)
        ->sequence(
            [
                'ai_execution_id' => $execution->getKey(),
                'sequence' => 1,
                'provider' => 'gemini',
                'status' => AiExecutionStatus::FAILED,
            ],
            [
                'ai_execution_id' => $execution->getKey(),
                'sequence' => 2,
                'provider' => 'ollama',
                'status' => AiExecutionStatus::SUCCEEDED,
            ],
        )
        ->create();

    Livewire::test(
        AttemptsRelationManager::class,
        [
            'ownerRecord' => $execution,
            'pageClass' => ViewAiExecution::class,
        ],
    )
        ->assertSuccessful()
        ->assertCanSeeTableRecords($attempts);
});

it('does not expose mutation actions on the attempts relation manager', function (): void {
    $execution = AiExecution::factory()->create();

    Livewire::test(
        AttemptsRelationManager::class,
        [
            'ownerRecord' => $execution,
            'pageClass' => ViewAiExecution::class,
        ],
    )
        ->assertSuccessful()
        ->assertActionDoesNotExist(
            TestAction::make('create')->table()
        )
        ->assertActionDoesNotExist(
            TestAction::make('edit')->table()
        )
        ->assertActionDoesNotExist(
            TestAction::make('delete')->table()
        );
});
