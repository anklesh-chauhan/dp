<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingPrograms\Pages;

use App\Domain\TMS\Services\TrainingProgramAssignmentService;
use App\Filament\Resources\TrainingPrograms\TrainingProgramResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;

final class ViewTrainingProgram extends ViewRecord
{
    protected static string $resource = TrainingProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignProgram')
                ->label('Assign program')
                ->visible(fn (): bool => (bool) auth()->user()?->can('Assign:TrainingProgram'))
                ->schema([
                    Select::make('user_ids')
                        ->label('Trainees')
                        ->multiple()
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    app(TrainingProgramAssignmentService::class)->assign(
                        $this->getRecord(),
                        auth()->user(),
                        $data['user_ids'] ?? [],
                    );
                }),
        ];
    }
}
