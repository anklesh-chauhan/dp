<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\RelationManagers;

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Enums\CsvTestType;
use App\Domain\QMS\Models\CsvTestCase;
use App\Domain\QMS\Services\CsvTestCaseApprovalService;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

final class TestCasesRelationManager extends RelationManager
{
    protected static string $relationship = 'testCases';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('test_identifier')
                    ->required()
                    ->maxLength(255),
                TextInput::make('version')->numeric()->default(1)->required(),
                Select::make('type')->options(self::options(CsvTestType::cases()))->required(),
                TextInput::make('title')->required(),
                Textarea::make('objective')->required(),
                Textarea::make('preconditions'),
                Textarea::make('test_data'),
                Repeater::make('steps')->schema([
                    TextInput::make('step')->required(),
                    Textarea::make('expected_result')->required(),
                ])->columns(2)->minItems(1)->required()->columnSpanFull(),
                Select::make('criticality')->options(self::options(CsvCriticality::cases()))->required(),
                Select::make('requirements')
                    ->relationship('requirements', 'requirement_identifier')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('test_identifier')
            ->columns([
                TextColumn::make('test_identifier')
                    ->searchable(),
                TextColumn::make('version'),
                TextColumn::make('type')->badge(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('approver.name')->label('Approved By')->placeholder('—'),
                TextColumn::make('approved_at')->dateTime()->placeholder('—'),
                TextColumn::make('requirements_count')->counts('requirements')->label('Requirements'),
                TextColumn::make('executions_count')->counts('executions')->label('Runs'),
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
                    ->disabled(fn (CsvTestCase $record): bool => $record->status === CsvRequirementStatus::Approved),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->schema([
                        ApprovalNarrativeTextarea::decisionRationale(
                            name: 'reason',
                            label: 'Approval reason',
                            helperText: 'Explain what you reviewed and why you are approving this test case. This text becomes part of the signed decision record.',
                            context: fn (): array => [
                                'record_type' => 'CSV test case approval',
                                'decision' => 'Approve',
                            ],
                        ),
                    ])
                    ->visible(fn (CsvTestCase $record): bool => $record->status === CsvRequirementStatus::Draft
                        && (bool) auth()->user()?->can('Test:CsvValidationProject'))
                    ->action(function (array $data, CsvTestCase $record, Action $action): void {
                        /** @var User $user */
                        $user = auth()->user();

                        try {
                            app(CsvTestCaseApprovalService::class)->approve(
                                $record,
                                $user,
                                $data['reason'],
                                request()->ip(),
                                request()->userAgent(),
                            );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Test case approval blocked')
                                ->body(collect($exception->errors())->flatten()->unique()->implode("\n"))
                                ->persistent()
                                ->send();

                            $action->halt();

                            return;
                        }

                        Notification::make()->success()->title('Test case approved')->send();
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
