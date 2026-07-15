<?php

declare(strict_types=1);

use App\Filament\Resources\AiExecutions\Pages\ListAiExecutions;
use App\Filament\Resources\AiExecutions\Widgets\AiProviderPerformanceTable;
use App\Models\AiExecution;
use App\Models\AiExecutionAttempt;
use App\Services\AI\Enums\AiExecutionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the provider performance table', function (): void {
    Livewire::test(AiProviderPerformanceTable::class)
        ->assertSuccessful();
});

it('groups provider attempts into performance rows', function (): void {
    $execution = AiExecution::factory()->create();

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $execution->getKey(),
        'sequence' => 1,
        'provider' => 'gemini',
        'status' => AiExecutionStatus::SUCCEEDED,
        'input_tokens' => 100,
        'output_tokens' => 50,
        'duration_ms' => 500,
    ]);

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $execution->getKey(),
        'sequence' => 2,
        'provider' => 'ollama',
        'status' => AiExecutionStatus::FAILED,
        'input_tokens' => null,
        'output_tokens' => null,
        'duration_ms' => 1_500,
    ]);

    Livewire::test(AiProviderPerformanceTable::class)
        ->assertSuccessful()
        ->assertSee('gemini')
        ->assertSee('ollama');
});

it('calculates provider performance metrics correctly', function (): void {
    $firstExecution = AiExecution::factory()->create();

    $secondExecution = AiExecution::factory()->create();

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $firstExecution->getKey(),
        'sequence' => 1,
        'provider' => 'gemini',
        'status' => AiExecutionStatus::SUCCEEDED,
        'input_tokens' => 100,
        'output_tokens' => 50,
        'duration_ms' => 500,
    ]);

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $secondExecution->getKey(),
        'sequence' => 1,
        'provider' => 'gemini',
        'status' => AiExecutionStatus::FAILED,
        'input_tokens' => 200,
        'output_tokens' => 100,
        'duration_ms' => 1_500,
    ]);

    Livewire::test(AiProviderPerformanceTable::class)
        ->assertSuccessful()
        ->assertSee('gemini')
        ->assertSee('2')
        ->assertSee('50.0%')
        ->assertSee('1.00 s')
        ->assertSee('300')
        ->assertSee('150')
        ->assertSee('450');
});

it('calculates metrics independently for each provider', function (): void {
    $firstExecution = AiExecution::factory()->create();

    $secondExecution = AiExecution::factory()->create();

    $thirdExecution = AiExecution::factory()->create();

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $firstExecution->getKey(),
        'sequence' => 1,
        'provider' => 'gemini',
        'status' => AiExecutionStatus::SUCCEEDED,
        'duration_ms' => 500,
    ]);

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $secondExecution->getKey(),
        'sequence' => 1,
        'provider' => 'gemini',
        'status' => AiExecutionStatus::SUCCEEDED,
        'duration_ms' => 1_500,
    ]);

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $thirdExecution->getKey(),
        'sequence' => 1,
        'provider' => 'ollama',
        'status' => AiExecutionStatus::FAILED,
        'duration_ms' => 2_000,
    ]);

    Livewire::test(AiProviderPerformanceTable::class)
        ->assertSuccessful()
        ->assertSee('gemini')
        ->assertSee('ollama')
        ->assertSee('100.0%')
        ->assertSee('0.0%');
});

it('excludes null durations from the average duration calculation', function (): void {
    $firstExecution = AiExecution::factory()->create();

    $secondExecution = AiExecution::factory()->create();

    $thirdExecution = AiExecution::factory()->create();

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $firstExecution->getKey(),
        'sequence' => 1,
        'provider' => 'gemini',
        'status' => AiExecutionStatus::SUCCEEDED,
        'duration_ms' => 500,
    ]);

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $secondExecution->getKey(),
        'sequence' => 1,
        'provider' => 'gemini',
        'status' => AiExecutionStatus::SUCCEEDED,
        'duration_ms' => 1_500,
    ]);

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $thirdExecution->getKey(),
        'sequence' => 1,
        'provider' => 'gemini',
        'status' => AiExecutionStatus::RUNNING,
        'duration_ms' => null,
    ]);

    Livewire::test(AiProviderPerformanceTable::class)
        ->assertSuccessful()
        ->assertSee('1.00 s');
});

it('counts running attempts in total attempts without treating them as failures', function (): void {
    $firstExecution = AiExecution::factory()->create();

    $secondExecution = AiExecution::factory()->create();

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $firstExecution->getKey(),
        'sequence' => 1,
        'provider' => 'gemini',
        'status' => AiExecutionStatus::SUCCEEDED,
    ]);

    AiExecutionAttempt::factory()->create([
        'ai_execution_id' => $secondExecution->getKey(),
        'sequence' => 1,
        'provider' => 'gemini',
        'status' => AiExecutionStatus::RUNNING,
    ]);

    Livewire::test(AiProviderPerformanceTable::class)
        ->assertSuccessful()
        ->assertSee('gemini')
        ->assertSee('2')
        ->assertSee('1')
        ->assertSee('0')
        ->assertSee('50.0%');
});

it('registers the provider performance table on the execution list page', function (): void {
    Livewire::test(ListAiExecutions::class)
        ->assertSuccessful()
        ->assertSeeLivewire(AiProviderPerformanceTable::class);
});
