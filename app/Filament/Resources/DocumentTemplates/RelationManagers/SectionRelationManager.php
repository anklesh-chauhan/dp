<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplates\RelationManagers;

use App\Enums\ProductModule;
use App\Filament\Concerns\ManagesEditableTemplates;
use App\Jobs\CompleteTemplateSectionWithAiJob;
use App\Jobs\GenerateTemplateSectionTitlesJob;
use App\Models\DocumentTemplateSection;
use App\Models\DocumentTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\VariableDataType;
use App\Services\AI\Contracts\TemplateGenerator;
use App\Support\Modules\ModuleManager;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SectionRelationManager extends RelationManager
{
    use ManagesEditableTemplates;

    protected static string $relationship = 'sections';

    public function form(Schema $schema): Schema
    {
        $aiEnabled = app(ModuleManager::class)->enabled(ProductModule::AI);

        return $schema->columns(1)->components([

            Grid::make(4)->schema([
                Select::make('template_version_id')
                    ->relationship(
                        name: 'templateVersion',
                        titleAttribute: 'version',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->where('document_template_id', $this->getOwnerRecord()->getKey())
                            ->orderByDesc('version'),
                    )
                    ->required(),
                TextInput::make('section_order')->numeric()->required()->minValue(1),
                TextInput::make('heading_level')->numeric()->minValue(1)->maxValue(6)->default(1)->required(),
                TextInput::make('section_type')->default('rich_text')->required(),
                Toggle::make('is_required')->default(true)->inline(false),
                Toggle::make('include_in_toc')->label('Include in TOC')->default(true)->inline(false),
            ]),

            Grid::make(1)->schema([
                TextInput::make('title')->required()->maxLength(255),
                TextInput::make('toc_title')->label('TOC title override')->maxLength(255),

                RichEditor::make('content')
                    ->columnSpanFull()
                    ->hintActions(
                        $aiEnabled ? [
                            Action::make('aiContentAssistant')
                                ->label('Create')
                                ->icon('heroicon-m-sparkles')
                                ->action(fn (Get $get, Set $set) => $this->applyAiContent('generate', $get, $set)),

                            Action::make('polishContentWithAi')
                                ->label('Polish')
                                ->icon('heroicon-m-document-text')
                                ->action(fn (Get $get, Set $set) => $this->applyAiContent('polish', $get, $set)),

                            Action::make('shortenContentWithAi')
                                ->label('Shorten')
                                ->icon('heroicon-m-scissors')
                                ->action(fn (Get $get, Set $set) => $this->applyAiContent('shorten', $get, $set)),
                        ] : []
                    ),
            ]),
        ]);
    }

    private function applyAiContent(string $operation, Get $get, Set $set): void
    {
        $content = trim((string) ($get('content') ?? ''));
        $title = trim((string) ($get('title') ?? 'Untitled section'));
        $template = $this->getOwnerRecord()->loadMissing(['department', 'regulationTags']);
        $versionId = (int) ($get('template_version_id') ?? 0);
        $version = $template->versions()->with('variables')->find($versionId);
        $existingVariables = $version?->variables->map(fn ($variable): array => [
            'name' => $variable->name,
            'label' => $variable->label,
            'datatype' => $variable->variableDataType?->code ?? 'text',
        ])->all() ?? [];

        $result = app(TemplateGenerator::class)->transformSectionContent(
            content: $content,
            operation: $operation,
            sectionTitle: $title,
            templateContext: [
                'description' => $template->description,
                'department' => $template->department?->name,
                'regulatory_tags' => $template->regulationTags->pluck('name')->all(),
                'existing_variables' => $existingVariables,
            ],
        );

        if ($result === null) {
            Notification::make()->danger()->title('AI content generation failed')->send();

            return;
        }

        $set('content', $result['content']);

        if ($version !== null) {
            $dataTypes = VariableDataType::query()
                ->pluck('id', 'code')
                ->mapWithKeys(fn (mixed $id, mixed $code): array => [strtolower((string) $code) => $id])
                ->all();
            $fallbackDataTypeId = $dataTypes['text'] ?? collect($dataTypes)->first();

            foreach ($result['variables'] as $variable) {
                $name = trim((string) ($variable['name'] ?? ''));

                if ($name === '' || $version->variables()->where('name', $name)->exists()) {
                    continue;
                }

                $version->variables()->create([
                    'name' => $name,
                    'label' => trim((string) ($variable['label'] ?? $name)),
                    'variable_data_type_id' => $dataTypes[strtolower((string) ($variable['datatype'] ?? 'text'))]
                        ?? $fallbackDataTypeId,
                    'default_value' => (string) ($variable['default_value'] ?? ''),
                    'required' => (bool) ($variable['required'] ?? false),
                ]);
            }
        }
        Notification::make()->success()->title('AI content updated')->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('templateVersion.version')->label('Version')->sortable(),
                TextColumn::make('section_order')->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('section_type')->badge(),
                IconColumn::make('is_required')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => $this->canManageTemplateRecord()),
                Action::make('generateSectionTitlesWithAi')
                    ->label('Generate Section Names with AI')
                    ->icon('heroicon-m-sparkles')
                    ->visible(fn (): bool => $this->canManageTemplateRecord() && app(ModuleManager::class)->enabled(ProductModule::AI))
                    ->action(function (): void {
                        $version = $this->getOwnerRecord()->versions()->latest('version')->first();
                        if (! $version instanceof DocumentTemplateVersion) {
                            $version = $this->getOwnerRecord()->versions()->create([
                                'version' => 1,
                                'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
                                'change_reason' => 'Draft version created for AI section generation.',
                                'created_by' => Auth::id(),
                            ]);
                        }

                        GenerateTemplateSectionTitlesJob::dispatch($version);

                        Notification::make()
                            ->success()
                            ->title('Section names generation started')
                            ->body('AI will replace existing section names or create sections in the background.')
                            ->send();
                    })->visible(fn (): bool => $this->canManageTemplateRecord()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->canManageTemplateRecord()),
                Action::make('completeWithAi')
                    ->label('Complete with AI')
                    ->icon('heroicon-m-sparkles')
                    ->visible(fn (): bool => $this->canManageTemplateRecord() && app(ModuleManager::class)->enabled(ProductModule::AI))
                    ->action(function (DocumentTemplateSection $record): void {
                        CompleteTemplateSectionWithAiJob::dispatch($record);

                        Notification::make()
                            ->success()
                            ->title('Section completion started')
                            ->body('AI will replace the existing section content in the background.')
                            ->send();
                    })
                    ->visible(fn (): bool => $this->canManageTemplateRecord()),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->canManageTemplateRecord()),
            ]);
    }
}
