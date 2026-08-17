<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\RelationManagers;

use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Enums\CsvSpecificationType;
use App\Domain\QMS\Models\CsvSpecification;
use App\Domain\QMS\Services\CsvSpecificationApprovalService;
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

final class SpecificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'specifications';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('specification_identifier')
                    ->required()
                    ->maxLength(255),
                TextInput::make('version')->numeric()->default(1)->required(),
                Select::make('type')->options(self::options(CsvSpecificationType::cases()))->required(),
                TextInput::make('title')->required(),
                Textarea::make('description')->required()->columnSpanFull(),
                Select::make('controlled_document_id')->relationship('controlledDocument', 'document_number')->searchable()->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('specification_identifier')
            ->columns([
                TextColumn::make('specification_identifier')
                    ->searchable(),
                TextColumn::make('version'),
                TextColumn::make('type')->badge(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('approver.name')->label('Approved By')->placeholder('—'),
                TextColumn::make('approved_at')->dateTime()->placeholder('—'),
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
                    ->disabled(fn (CsvSpecification $record): bool => $record->status === CsvRequirementStatus::Approved),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->schema([
                        ApprovalNarrativeTextarea::decisionRationale(
                            name: 'reason',
                            label: 'Approval reason',
                            helperText: 'Explain what you reviewed and why you are approving this specification. This text becomes part of the signed decision record.',
                            context: fn (): array => [
                                'record_type' => 'CSV specification approval',
                                'decision' => 'Approve',
                            ],
                        ),
                    ])
                    ->visible(fn (CsvSpecification $record): bool => $record->status === CsvRequirementStatus::Draft
                        && (bool) auth()->user()?->can('Specify:CsvValidationProject'))
                    ->action(function (array $data, CsvSpecification $record, Action $action): void {
                        /** @var User $user */
                        $user = auth()->user();

                        try {
                            app(CsvSpecificationApprovalService::class)->approve(
                                $record,
                                $user,
                                $data['reason'],
                                request()->ip(),
                                request()->userAgent(),
                            );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Specification approval blocked')
                                ->body(collect($exception->errors())->flatten()->unique()->implode("\n"))
                                ->persistent()
                                ->send();

                            $action->halt();

                            return;
                        }

                        Notification::make()->success()->title('Specification approved')->send();
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
