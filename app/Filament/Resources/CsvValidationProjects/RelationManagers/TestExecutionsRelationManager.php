<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\RelationManagers;

use App\Domain\QMS\Enums\CsvExecutionResult;
use App\Domain\QMS\Models\CsvTestExecution;
use App\Domain\QMS\Services\CsvTestExecutionReviewService;
use App\Enums\ProductModule;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

final class TestExecutionsRelationManager extends RelationManager
{
    protected static string $relationship = 'testExecutions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('csv_test_case_id')
                    ->relationship('testCase', 'test_identifier')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('execution_no')->numeric()->minValue(1)->required(),
                TextInput::make('environment')->required(),
                TextInput::make('application_version')->required(),
                TextInput::make('commit_sha'),
                TextInput::make('configuration_hash')->maxLength(64),
                Repeater::make('step_results')->schema([
                    TextInput::make('step')->required(),
                    Select::make('result')->options(['passed' => 'Passed', 'failed' => 'Failed', 'blocked' => 'Blocked'])->required(),
                    Textarea::make('actual_result')->required(),
                    TextInput::make('evidence_reference'),
                ])->columns(2)->minItems(1)->required()->columnSpanFull(),
                Select::make('result')->options(self::options(CsvExecutionResult::cases()))->required(),
                Textarea::make('actual_result')->required()->columnSpanFull(),
                Textarea::make('evidence_summary')->columnSpanFull(),
                Select::make('deviation_id')->relationship('deviation', 'deviation_number')->searchable()->preload(),
                Select::make('executed_by')->relationship('executor', 'name')->searchable()->preload()->required(),
                DateTimePicker::make('started_at')->required(),
                DateTimePicker::make('completed_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('execution_uuid')
            ->columns([
                TextColumn::make('testCase.test_identifier')->label('Test'),
                TextColumn::make('execution_no')->label('Run'),
                TextColumn::make('application_version')->label('Version'),
                TextColumn::make('result')->badge(),
                TextColumn::make('executor.name')->label('Executor'),
                TextColumn::make('reviewer.name')->label('Reviewer')->placeholder('—'),
                TextColumn::make('reviewed_at')->dateTime()->placeholder('—'),
                TextColumn::make('attachments_count')->counts('attachments')->label('Evidence'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->disabled(fn (CsvTestExecution $record): bool => $record->reviewed_at !== null),
                Action::make('uploadEvidence')
                    ->label('Upload Evidence')
                    ->schema([
                        FileUpload::make('path')
                            ->label('Attachment')
                            ->disk('local')
                            ->directory('qms/quality-attachments')
                            ->visibility('private')
                            ->preventFilePathTampering()
                            ->storeFileNamesIn('original_name')
                            ->maxSize(10_240)
                            ->required(),
                        TextInput::make('annexure_number')
                            ->label('Annexure number')
                            ->placeholder('Annexure A, B, or 1.1')
                            ->maxLength(50),
                        Select::make('attachment_role')
                            ->label('Attachment role')
                            ->options([
                                'supporting_evidence' => 'Supporting evidence',
                                'drawing' => 'Drawing / layout',
                                'certificate' => 'Certificate',
                                'photograph' => 'Photograph',
                                'calculation' => 'Calculation',
                                'sample' => 'Sample / label',
                            ])
                            ->default('supporting_evidence')
                            ->required(),
                        Toggle::make('is_required')->label('Required annexure'),
                        Toggle::make('include_in_print')->label('Include in print package')->default(true),
                        Textarea::make('description')
                            ->maxLength(2_000),
                    ])
                    ->visible(fn (CsvTestExecution $record): bool => $record->reviewed_at === null
                        && (bool) auth()->user()?->can('Create:QualityAttachment'))
                    ->authorize(fn (): bool => app(ModuleManager::class)->enabled(ProductModule::QMS)
                        && (bool) auth()->user()?->can('Create:QualityAttachment'))
                    ->action(function (array $data, CsvTestExecution $record): void {
                        app(ModuleManager::class)->ensureEnabled(ProductModule::QMS);

                        if (! auth()->user()?->can('Create:QualityAttachment')) {
                            throw new AuthorizationException('You do not have permission to upload quality evidence.');
                        }

                        $record->attachments()->create([
                            'disk' => 'local',
                            'path' => $data['path'],
                            'original_name' => $data['original_name'] ?? null,
                            'annexure_number' => $data['annexure_number'] ?? null,
                            'attachment_role' => $data['attachment_role'] ?? 'supporting_evidence',
                            'is_required' => (bool) ($data['is_required'] ?? false),
                            'include_in_print' => (bool) ($data['include_in_print'] ?? true),
                            'description' => $data['description'] ?? null,
                            'uploaded_by' => auth()->id(),
                            'uploaded_at' => now(),
                        ]);

                        Notification::make()->success()->title('Evidence uploaded')->send();
                    }),
                Action::make('review')
                    ->label('Review')
                    ->color('success')
                    ->schema([
                        ApprovalNarrativeTextarea::decisionRationale(
                            name: 'reason',
                            label: 'Review reason',
                            helperText: 'Explain what evidence you reviewed and why you accept this execution outcome. This text becomes part of the signed decision record.',
                            context: fn (): array => [
                                'record_type' => 'CSV test execution review',
                                'decision' => 'Review',
                            ],
                        ),
                    ])
                    ->visible(fn (CsvTestExecution $record): bool => $record->reviewed_at === null
                        && $record->completed_at !== null
                        && (bool) auth()->user()?->can('Review:CsvValidationProject'))
                    ->action(function (array $data, CsvTestExecution $record, Action $action): void {
                        /** @var User $user */
                        $user = auth()->user();

                        try {
                            app(CsvTestExecutionReviewService::class)->review(
                                $record,
                                $user,
                                $data['reason'],
                                request()->ip(),
                                request()->userAgent(),
                            );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Execution review blocked')
                                ->body(collect($exception->errors())->flatten()->unique()->implode("\n"))
                                ->persistent()
                                ->send();

                            $action->halt();

                            return;
                        }

                        Notification::make()->success()->title('Execution reviewed')->send();
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
