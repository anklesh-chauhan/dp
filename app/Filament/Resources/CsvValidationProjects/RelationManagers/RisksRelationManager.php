<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\RelationManagers;

use App\Domain\QMS\Models\CsvRiskAssessment;
use App\Domain\QMS\Services\CsvRiskAcceptanceService;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

final class RisksRelationManager extends RelationManager
{
    protected static string $relationship = 'risks';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('risk_identifier')
                    ->required()
                    ->maxLength(255),
                Select::make('csv_requirement_id')->relationship('requirement', 'requirement_identifier')->searchable()->preload(),
                Textarea::make('hazard')->required(),
                Textarea::make('potential_impact')->required(),
                Textarea::make('existing_controls'),
                TextInput::make('initial_severity')->numeric()->minValue(1)->maxValue(5)->required(),
                TextInput::make('initial_probability')->numeric()->minValue(1)->maxValue(5)->required(),
                TextInput::make('initial_detectability')->numeric()->minValue(1)->maxValue(5)->required(),
                Textarea::make('mitigation'),
                TextInput::make('residual_severity')->numeric()->minValue(1)->maxValue(5),
                TextInput::make('residual_probability')->numeric()->minValue(1)->maxValue(5),
                TextInput::make('residual_detectability')->numeric()->minValue(1)->maxValue(5),
                Textarea::make('acceptance_rationale'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('risk_identifier')
            ->columns([
                TextColumn::make('risk_identifier')
                    ->searchable(),
                TextColumn::make('requirement.requirement_identifier')->label('Requirement'),
                TextColumn::make('initial_rpn')->state(fn ($record): int => $record->initialRiskPriorityNumber())->label('Initial RPN'),
                TextColumn::make('residual_rpn')->state(fn ($record): ?int => $record->residualRiskPriorityNumber())->label('Residual RPN'),
                TextColumn::make('acceptor.name')->label('Accepted By')->placeholder('—'),
                TextColumn::make('accepted_at')->dateTime()->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->disabled(fn (CsvRiskAssessment $record): bool => $record->accepted_at !== null),
                Action::make('accept')
                    ->label('Accept Residual Risk')
                    ->color('success')
                    ->schema([
                        ApprovalNarrativeTextarea::decisionRationale(
                            name: 'reason',
                            label: 'Acceptance reason',
                            helperText: 'Explain why residual risk is acceptable for intended use. This text becomes part of the signed decision record.',
                            context: fn (): array => [
                                'record_type' => 'CSV residual risk acceptance',
                                'decision' => 'Accept',
                            ],
                        ),
                    ])
                    ->visible(fn (CsvRiskAssessment $record): bool => $record->accepted_at === null
                        && (bool) auth()->user()?->can('Review:CsvValidationProject'))
                    ->action(function (array $data, CsvRiskAssessment $record, Action $action): void {
                        /** @var User $user */
                        $user = auth()->user();

                        try {
                            app(CsvRiskAcceptanceService::class)->accept(
                                $record,
                                $user,
                                $data['reason'],
                                request()->ip(),
                                request()->userAgent(),
                            );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Risk acceptance blocked')
                                ->body(collect($exception->errors())->flatten()->unique()->implode("\n"))
                                ->persistent()
                                ->send();

                            $action->halt();

                            return;
                        }

                        Notification::make()->success()->title('Residual risk accepted')->send();
                    }),
            ]);
    }
}
