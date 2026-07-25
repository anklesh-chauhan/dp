<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\RelationManagers;

use App\Domain\Shared\Services\AuditLogService;
use App\Filament\Concerns\ManagesEditableTemplates;
use App\Models\SopAuditLog;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class VersionRelationManager extends RelationManager
{
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
                TextColumn::make('templateStatus.name')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('sections_count')->counts('sections')->label('Sections'),
                TextColumn::make('variables_count')->counts('variables')->label('Variables'),
                TextColumn::make('effective_date')->date(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->canManageTemplateRecord())
                    ->using(function (SopTemplateVersion $record, array $data): SopTemplateVersion {
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
                    ->visible(fn (): bool => $this->canManageTemplateRecord()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => $this->canManageTemplateRecord())
                    ->mutateDataUsing(function (array $data): array {
                        $data['template_status_id'] = TemplateStatus::idFor(TemplateStatus::DRAFT);
                        $data['created_by'] = Auth::id();

                        return $data;
                    })
                    ->after(function (SopTemplateVersion $record): void {
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
            ]);
    }
}
