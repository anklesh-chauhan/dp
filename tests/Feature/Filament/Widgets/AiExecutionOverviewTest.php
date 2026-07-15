<?php

declare(strict_types=1);

use App\Filament\Resources\AiExecutions\Pages\ListAiExecutions;
use App\Filament\Resources\AiExecutions\Widgets\AiExecutionOverview;
use App\Models\AiExecution;
use App\Services\AI\Enums\AiExecutionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the ai execution overview widget', function (): void {
    Livewire::test(AiExecutionOverview::class)
        ->assertSuccessful();
});

it('renders zero state metrics when no executions exist', function (): void {
    Livewire::test(AiExecutionOverview::class)
        ->assertSuccessful()
        ->assertSee('Total Executions')
        ->assertSee('0')
        ->assertSee('Success Rate')
        ->assertSee('0.0%')
        ->assertSee('Failure Rate')
        ->assertSee('Fallback Rate')
        ->assertSee('Average Duration')
        ->assertSee('—')
        ->assertSee('Total Tokens');
});

it('calculates execution metrics correctly', function (): void {
    AiExecution::factory()->create([
        'status' => AiExecutionStatus::SUCCEEDED,
        'attempt_count' => 1,
        'input_tokens' => 100,
        'output_tokens' => 50,
        'duration_ms' => 500,
    ]);

    AiExecution::factory()->create([
        'status' => AiExecutionStatus::SUCCEEDED,
        'attempt_count' => 2,
        'input_tokens' => 200,
        'output_tokens' => 100,
        'duration_ms' => 1_500,
    ]);

    AiExecution::factory()->create([
        'status' => AiExecutionStatus::FAILED,
        'attempt_count' => 2,
        'input_tokens' => null,
        'output_tokens' => null,
        'duration_ms' => 1_000,
    ]);

    AiExecution::factory()->create([
        'status' => AiExecutionStatus::RUNNING,
        'attempt_count' => 0,
        'input_tokens' => null,
        'output_tokens' => null,
        'duration_ms' => null,
    ]);

    Livewire::test(AiExecutionOverview::class)
        ->assertSuccessful()
        ->assertSee('Total Executions')
        ->assertSee('4')
        ->assertSee('Success Rate')
        ->assertSee('50.0%')
        ->assertSee('Failure Rate')
        ->assertSee('25.0%')
        ->assertSee('Fallback Rate')
        ->assertSee('50.0%')
        ->assertSee('Average Duration')
        ->assertSee('1.00 s')
        ->assertSee('Total Tokens')
        ->assertSee('450');
});

it('calculates average duration using only recorded durations', function (): void {
    AiExecution::factory()->create([
        'status' => AiExecutionStatus::SUCCEEDED,
        'duration_ms' => 500,
    ]);

    AiExecution::factory()->create([
        'status' => AiExecutionStatus::SUCCEEDED,
        'duration_ms' => 1_500,
    ]);

    AiExecution::factory()->create([
        'status' => AiExecutionStatus::RUNNING,
        'duration_ms' => null,
    ]);

    Livewire::test(AiExecutionOverview::class)
        ->assertSuccessful()
        ->assertSee('Average Duration')
        ->assertSee('1.00 s');
});

it('counts executions with multiple attempts as fallback executions', function (): void {
    AiExecution::factory()->create([
        'status' => AiExecutionStatus::SUCCEEDED,
        'attempt_count' => 1,
    ]);

    AiExecution::factory()->create([
        'status' => AiExecutionStatus::SUCCEEDED,
        'attempt_count' => 2,
    ]);

    AiExecution::factory()->create([
        'status' => AiExecutionStatus::FAILED,
        'attempt_count' => 3,
    ]);

    Livewire::test(AiExecutionOverview::class)
        ->assertSuccessful()
        ->assertSee('Fallback Rate')
        ->assertSee('66.7%')
        ->assertSee('2 used fallback');
});

it('registers the overview widget on the execution list page', function (): void {
    Livewire::test(ListAiExecutions::class)
        ->assertSuccessful()
        ->assertSeeLivewire(AiExecutionOverview::class);
});
