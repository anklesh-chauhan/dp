<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplates\RelationManagers;

use App\Domain\DMS\Actions\CreateTemplateDraftRevisionAction;
use App\Domain\Shared\Services\AuditLogService;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\ManagesEditableTemplates;
use App\Models\DocumentTemplateVersion;
use App\Models\SopAuditLog;
use App\Models\TemplateStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class VersionRelationManager extends RelationManager
{
    use HandlesServiceExceptions;
    use ManagesEditableTemplates;

    protected static string $relationship = 'versions';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('version')->numeric()->required()->minValue(1),
                DatePicker::make('effective_date'),
                Textarea::make('change_reason')->columnSpanFull(),
                KeyValue::make('content_json')->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->columns([
                TextColumn::make('version')->sortable(),
                TextColumn::make('approval_status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state): string => $state->label())
                    ->badge()
                    ->color(fn ($state): string => match ($state->value) {
                        'approved' => 'success',
                        'submitted', 'reviewed' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('templateStatus.name')
                    ->label('Lifecycle')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sections_count')->counts('sections')->label('Sections'),
                TextColumn::make('variables_count')->counts('variables')->label('Variables'),
                TextColumn::make('effective_date')->date(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (DocumentTemplateVersion $record): bool => $this->canManageTemplateRecord()
                        && $record->isContentEditable())
                    ->using(function (DocumentTemplateVersion $record, array $data): DocumentTemplateVersion {
                        $oldValues = [
                            'version' => $record->version,
                            'change_reason' => $record->change_reason,
                            'effective_date' => $record->effective_date?->toDateString(),
                        ];

                        $record->update($data);

                        app(AuditLogService::class)->log(
                            action: SopAuditLog::ACTION_VERSION_UPDATED,
                            oldValues: $oldValues,
                            newValues: [
                                'template_version_id' => $record->id,
                                'version' => $record->version,
                                'change_reason' => $record->change_reason,
                                'effective_date' => $record->effective_date?->toDateString(),
                            ],
                            userId: Auth::id(),
                            template: $this->getOwnerRecord(),
                        );

                        return $record;
                    }),
                DeleteAction::make()
                    ->visible(fn (DocumentTemplateVersion $record): bool => $this->canManageTemplateRecord()
                        && $record->isContentEditable()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => $this->canManageTemplateRecord()
                        && ! $this->getOwnerRecord()->templateStatus?->hasCode(TemplateStatus::PUBLISHED))
                    ->mutateDataUsing(function (array $data): array {
                        $data['template_status_id'] = TemplateStatus::idFor(TemplateStatus::DRAFT);
                        $data['created_by'] = Auth::id();

                        return $data;
                    })
                    ->after(function (DocumentTemplateVersion $record): void {
                        app(AuditLogService::class)->log(
                            action: SopAuditLog::ACTION_VERSION_CREATED,
                            newValues: [
                                'template_version_id' => $record->id,
                                'version' => $record->version,
                                'status' => $record->templateStatus?->code,
                                'change_reason' => $record->change_reason,
                            ],
                            userId: Auth::id(),
                            template: $this->getOwnerRecord(),
                        );
                    }),
                Action::make('createDraftRevision')
                    ->label('Create Draft Revision')
                    ->icon(Heroicon::DocumentDuplicate)
                    ->color('warning')
                    ->visible(fn (): bool => $this->canStartDraftRevision())
                    ->modalHeading('Create a draft revision?')
                    ->modalDescription('The published version stays locked. Sections and variables are cloned into a new draft version.')
                    ->modalSubmitActionLabel('Create draft revision')
                    ->schema([
                        Textarea::make('change_reason')
                            ->label('Revision reason')
                            ->required()
                            ->maxLength(2_000),
                    ])
                    ->action(function (array $data): void {
                        /** @var User $user */
                        $user = Auth::user();

                        $this->runServiceAction(
                            fn () => app(CreateTemplateDraftRevisionAction::class)->execute(
                                $this->getOwnerRecord(),
                                $user,
                                $data['change_reason'],
                            ),
                            failureTitle: 'Draft Revision Failed',
                            successTitle: 'Draft revision created',
                            successBody: 'The published version remains locked. Edit the new draft version to continue.',
                        );
                    }),
            ]);
    }
}
