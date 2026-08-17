<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\RelationManagers;

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Models\CsvRequirement;
use App\Domain\QMS\Services\CsvRequirementApprovalService;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

final class RequirementsRelationManager extends RelationManager
{
    protected static string $relationship = 'requirements';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('requirement_identifier')
                    ->required()
                    ->maxLength(255),
                TextInput::make('version')->numeric()->default(1)->required(),
                TextInput::make('category')->required(),
                Textarea::make('statement')->required()->columnSpanFull(),
                Textarea::make('acceptance_criteria')->required()->columnSpanFull(),
                Textarea::make('rationale')->columnSpanFull(),
                TextInput::make('source_reference'),
                Select::make('criticality')->options(self::options(CsvCriticality::cases()))->required(),
                Select::make('owner_id')->relationship('owner', 'name')->searchable()->preload(),
                Checkbox::make('gxp_relevant')->default(true),
                Checkbox::make('data_integrity_relevant'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('requirement_identifier')
            ->columns([
                TextColumn::make('requirement_identifier')
                    ->searchable(),
                TextColumn::make('version'),
                TextColumn::make('category')->badge(),
                TextColumn::make('criticality')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('approver.name')->label('Approved By')->placeholder('—'),
                TextColumn::make('approved_at')->dateTime()->placeholder('—'),
                TextColumn::make('test_cases_count')->counts('testCases')->label('Tests'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['status'] = CsvRequirementStatus::Draft->value;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->disabled(fn (CsvRequirement $record): bool => $record->status === CsvRequirementStatus::Approved),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->schema([
                        ApprovalNarrativeTextarea::decisionRationale(
                            name: 'reason',
                            label: 'Approval reason',
                            helperText: 'Explain what you reviewed and why you are approving this requirement. This text becomes part of the signed decision record.',
                            context: fn (): array => [
                                'record_type' => 'CSV requirement approval',
                                'decision' => 'Approve',
                            ],
                        ),
                    ])
                    ->visible(fn (CsvRequirement $record): bool => $record->status === CsvRequirementStatus::Draft
                        && (bool) auth()->user()?->can('Specify:CsvValidationProject'))
                    ->action(function (array $data, CsvRequirement $record, Action $action): void {
                        /** @var User $user */
                        $user = auth()->user();

                        try {
                            app(CsvRequirementApprovalService::class)->approve(
                                $record,
                                $user,
                                $data['reason'],
                                request()->ip(),
                                request()->userAgent(),
                            );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Requirement approval blocked')
                                ->body(collect($exception->errors())->flatten()->unique()->implode("\n"))
                                ->persistent()
                                ->send();

                            $action->halt();

                            return;
                        }

                        Notification::make()->success()->title('Requirement approved')->send();
                    }),
            ]);
    }

    private static function options(array $cases): array
    {
        return collect($cases)->mapWithKeys(fn (\BackedEnum $case): array => [
            $case->value => str($case->value)->replace('_', ' ')->title()->toString(),
        ])->all();
    }
}
